<?php

namespace App\Services\Finance;

use App\Models\Currency;
use App\Models\Finance\AccountingPeriod;
use App\Models\Finance\AccountType;
use App\Models\Finance\BankAccount;
use App\Models\Finance\BudgetCode;
use App\Models\Finance\ChartOfAccount;
use App\Models\Finance\JournalEntry;
use App\Models\Finance\JournalEntryLine;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class ImportService
{
    // ── Helpers ──────────────────────────────────────────────────────────

    /**
     * Load a spreadsheet and return the rows of a given sheet
     * (1-indexed; row 1 = headers which are skipped).
     *
     * @return Collection<int, array>
     */
    private function loadRows(string $path, int $sheetIndex = 0, int $headerRow = 1): Collection
    {
        $spreadsheet = IOFactory::load($path);
        $ws          = $spreadsheet->getSheet($sheetIndex);
        $maxRow      = $ws->getHighestDataRow();
        $maxCol      = $ws->getHighestDataColumn();

        $rows = collect();
        for ($row = $headerRow + 1; $row <= $maxRow; $row++) {
            $data = $ws->rangeToArray("A{$row}:{$maxCol}{$row}", null, true, false)[0];
            // Skip entirely blank rows
            if (collect($data)->filter(fn ($v) => $v !== null && (string) $v !== '')->isEmpty()) {
                continue;
            }
            $rows->push($data);
        }

        return $rows;
    }

    /**
     * Read the header row of a sheet and return column index → name mapping.
     */
    private function loadHeaders(string $path, int $sheetIndex = 0, int $headerRow = 1): array
    {
        $spreadsheet = IOFactory::load($path);
        $ws          = $spreadsheet->getSheet($sheetIndex);
        $maxCol      = $ws->getHighestDataColumn();
        $raw         = $ws->rangeToArray("A{$headerRow}:{$maxCol}{$headerRow}", null, true, false)[0];

        $headers = [];
        foreach ($raw as $i => $col) {
            if ($col !== null) {
                $headers[$i] = strtolower(trim((string) $col));
            }
        }
        return $headers;
    }

    // ── Chart of Accounts Import ──────────────────────────────────────────

    /**
     * Maps Excel "Type" column values → DB account_type classification.
     * DB has: asset | liability | equity | income | expense
     */
    private function resolveAccountTypeId(string $typeRaw, array $typeByClass, array $typeByName): ?int
    {
        if ($typeRaw === '') {
            return null;
        }

        $lower = strtolower($typeRaw);

        // 1) Direct classification match (asset, liability, equity, income, expense)
        if (isset($typeByClass[$lower])) {
            return $typeByClass[$lower];
        }

        // 2) Direct name match (Asset, Liability, etc.)
        if (isset($typeByName[$lower])) {
            return $typeByName[$lower];
        }

        // 3) Keyword mapping for the Excel "Type" values in sample files
        $keywordMap = [
            // → asset
            'asset'                  => 'asset',
            'bank'                   => 'asset',
            'cash'                   => 'asset',
            'receivable'             => 'asset',
            'prepaid'                => 'asset',
            'current asset'          => 'asset',
            'other current asset'    => 'asset',
            'fixed asset'            => 'asset',
            'non-current asset'      => 'asset',
            'property'               => 'asset',
            'equipment'              => 'asset',
            'advance'                => 'asset',
            'deposit'                => 'asset',
            'inventory'              => 'asset',
            'investment'             => 'asset',
            'suspense'               => 'asset',
            // → liability
            'liability'              => 'liability',
            'payable'                => 'liability',
            'accrued'                => 'liability',
            'current liability'      => 'liability',
            'other current liability'=> 'liability',
            'long-term'              => 'liability',
            'deferred'               => 'liability',
            'loan'                   => 'liability',
            'tax'                    => 'liability',
            'vat'                    => 'liability',
            'withholding'            => 'liability',
            // → equity
            'equity'                 => 'equity',
            'capital'                => 'equity',
            'retained'               => 'equity',
            'reserve'                => 'equity',
            'fund'                   => 'equity',
            'net asset'              => 'equity',
            // → income
            'income'                 => 'income',
            'revenue'                => 'income',
            'grant'                  => 'income',
            'donation'               => 'income',
            'interest income'        => 'income',
            'other income'           => 'income',
            // → expense
            'expense'                => 'expense',
            'cost'                   => 'expense',
            'salary'                 => 'expense',
            'depreciation'           => 'expense',
            'amortisation'           => 'expense',
            'amortization'           => 'expense',
            'administrative'         => 'expense',
        ];

        foreach ($keywordMap as $keyword => $classification) {
            if (str_contains($lower, $keyword)) {
                return $typeByClass[$classification] ?? null;
            }
        }

        return null;
    }

    /**
     * Import columns expected (case-insensitive):
     *   Type | sub_type | account_code | account_name | sub_account_of | Bank
     *
     * Returns ['imported' => N, 'skipped' => N, 'errors' => [string]]
     */
    public function importChartOfAccounts(string $filePath): array
    {
        $imported = 0;
        $skipped  = 0;
        $errors   = [];

        // Build lookup maps: classification → id, name → id
        $typeByClass = AccountType::all()->pluck('id', 'classification')
            ->mapWithKeys(fn ($id, $class) => [strtolower($class) => $id])
            ->toArray();
        $typeByName = AccountType::all()->pluck('id', 'name')
            ->mapWithKeys(fn ($id, $name) => [strtolower($name) => $id])
            ->toArray();

        try {
            $headers = $this->loadHeaders($filePath);
            $rows    = $this->loadRows($filePath);

            // Map header names to column indices
            $colType   = array_search('type', $headers);
            $colSubType= array_search('sub_type', $headers);
            $colCode   = array_search('account_code', $headers);
            $colName   = array_search('account_name', $headers);
            $colParent = array_search('sub_account_of', $headers);

            DB::beginTransaction();

            foreach ($rows as $rowIndex => $row) {
                /** @var array<int, mixed> $row */
                $typeRaw    = trim((string) ($row[$colType]    ?? ''));
                $subTypeRaw = trim((string) ($row[$colSubType] ?? ''));
                $codeRaw    = strtoupper(trim((string) ($row[$colCode]  ?? '')));
                $nameRaw    = trim((string) ($row[$colName]    ?? ''));
                $parentCode = trim((string) ($row[$colParent]  ?? ''));

                if ($codeRaw === '') {
                    $skipped++;
                    continue;
                }

                // Resolve account type using the smart keyword mapper
                $accountTypeId = $this->resolveAccountTypeId($typeRaw, $typeByClass, $typeByName);

                if ($accountTypeId === null) {
                    $errors[] = "Row {$codeRaw}: Type '{$typeRaw}' could not be mapped — defaulting to Asset.";
                    $accountTypeId = $typeByClass['asset'] ?? null;
                }

                // Resolve parent account
                $parentId = null;
                if ($parentCode !== '') {
                    $parentId = ChartOfAccount::where('code', $parentCode)->value('id');
                }

                // Update if already exists
                $existing = ChartOfAccount::where('code', $codeRaw)->first();
                if ($existing) {
                    $existing->fill([
                        'name'            => $nameRaw ?: $existing->name,
                        'account_type_id' => $accountTypeId ?? $existing->account_type_id,
                        'parent_id'       => $parentId ?? $existing->parent_id,
                        'notes'           => $subTypeRaw ?: $existing->notes,
                    ])->save();
                    $skipped++;
                    continue;
                }

                // Create new account
                ChartOfAccount::create([
                    'code'                  => $codeRaw,
                    'name'                  => $nameRaw ?: $codeRaw,
                    'account_type_id'       => $accountTypeId,
                    'parent_id'             => $parentId,
                    'is_active'             => true,
                    'is_header'             => false,
                    'is_control_account'    => 'none',
                    'is_donor_fund_account' => false,
                    'level'                 => $parentId ? 1 : 0,
                    'notes'                 => $subTypeRaw ?: null,
                ]);

                $imported++;
            }

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            $errors[] = 'Fatal: ' . $e->getMessage();
        }

        return compact('imported', 'skipped', 'errors');
    }


    // ── Bank Accounts Import ──────────────────────────────────────────────

    /**
     * Import columns expected:
     *   Type | sub_type | account_code | account_name | sub_account_of | Bank
     *
     * "Bank" column = bank SWIFT / identifier → used as bank_name.
     * "account_code" + "account_name" → stored as account_number + account_name.
     * "sub_type" → stored as account description/notes.
     *
     * Returns ['imported' => N, 'skipped' => N, 'errors' => [string]]
     */
    public function importBankAccounts(string $filePath): array
    {
        $imported = 0;
        $skipped  = 0;
        $errors   = [];

        // Default currency (ETB)
        $etbCurrency = Currency::where('code', 'ETB')->first();

        try {
            $headers = $this->loadHeaders($filePath);
            $rows    = $this->loadRows($filePath);

            $colType    = array_search('type', $headers);
            $colSubType = array_search('sub_type', $headers);
            $colCode    = array_search('account_code', $headers);
            $colName    = array_search('account_name', $headers);
            $colBank    = array_search('bank', $headers);

            DB::beginTransaction();

            foreach ($rows as $rowIndex => $row) {
                /** @var array<int, mixed> $row */
                $typeRaw    = strtolower(trim((string) ($row[$colType]    ?? '')));
                $subTypeRaw = trim((string) ($row[$colSubType] ?? ''));
                $codeRaw    = trim((string) ($row[$colCode]    ?? ''));
                $nameRaw    = trim((string) ($row[$colName]    ?? ''));
                $bankCode   = trim((string) ($row[$colBank]    ?? ''));

                // Only rows where Type == "Bank"
                if (! str_contains($typeRaw, 'bank')) {
                    $skipped++;
                    continue;
                }

                if ($codeRaw === '') {
                    $skipped++;
                    continue;
                }

                // Detect currency from account code (contains "USD", "EUR", etc.)
                $currency = $etbCurrency;
                foreach (['USD', 'EUR', 'GBP', 'EURO'] as $ccy) {
                    if (str_contains(strtoupper($codeRaw), $ccy) || str_contains(strtoupper($nameRaw), $ccy)) {
                        $found = Currency::where('code', $ccy === 'EURO' ? 'EUR' : $ccy)->first();
                        if ($found) {
                            $currency = $found;
                        }
                        break;
                    }
                }

                // Extract account number from sub_type (e.g. "Dashen Bank ETB(0012107632011)")
                $accountNumber = $codeRaw;
                if (preg_match('/\(([^)]+)\)/', $subTypeRaw, $m)) {
                    $accountNumber = $m[1];
                }

                // Extract bank name from sub_type
                $bankName = preg_replace('/\s*\([^)]*\)\s*/', '', $subTypeRaw);
                if ($bankName === '') {
                    $bankName = $bankCode ?: $codeRaw;
                }

                // Check duplicate by account_number (including soft-deleted records)
                $existing = BankAccount::withTrashed()
                    ->where('account_number', $accountNumber)
                    ->first();

                if ($existing) {
                    if ($existing->trashed()) {
                        // Restore soft-deleted record and update it
                        $existing->restore();
                        $existing->update([
                            'account_name'    => $nameRaw ?: $subTypeRaw,
                            'bank_name'       => $bankName,
                            'currency_id'     => $currency?->id,
                            'is_active'       => true,
                            'notes'           => "Imported: {$subTypeRaw}",
                        ]);
                        $imported++;
                    } else {
                        // Active record already exists — skip
                        $skipped++;
                    }
                    continue;
                }

                BankAccount::create([
                    'account_name'    => $nameRaw ?: $subTypeRaw,
                    'bank_name'       => $bankName,
                    'account_number'  => $accountNumber,
                    'branch'          => null,
                    'swift_code'      => null,
                    'account_type'    => 'current',
                    'currency_id'     => $currency?->id,
                    'is_active'       => true,
                    'opening_balance' => 0,
                    'current_balance' => 0,
                    'notes'           => "Imported: {$subTypeRaw}",
                ]);

                $imported++;
            }

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            $errors[] = 'Fatal: ' . $e->getMessage();
        }

        return compact('imported', 'skipped', 'errors');
    }

    // ── Journal Entries Import ────────────────────────────────────────────

    /**
     * Pre-flight check: scans all account codes used in the Excel and returns
     * any that are missing from the active Chart of Accounts.
     *
     * @return array<string>  List of missing account codes, empty if all OK.
     */
    private function missingAccountCodes(string $filePath): array
    {
        $headers    = $this->loadHeaders($filePath);
        $rows       = $this->loadRows($filePath);
        $colAccount = array_search('account', $headers);

        if ($colAccount === false) {
            return [];
        }

        // Collect every unique account code referenced in the file
        $usedCodes = $rows
            ->map(fn ($r) => strtoupper(trim((string) ($r[$colAccount] ?? ''))))
            ->filter(fn ($c) => $c !== '')
            ->unique()
            ->values();

        if ($usedCodes->isEmpty()) {
            return [];
        }

        // Build set of existing CoA codes (uppercase for comparison)
        $existingCodes = ChartOfAccount::where('is_active', true)
            ->pluck('code')
            ->map(fn ($c) => strtoupper(trim($c)))
            ->flip()
            ->toArray();

        return $usedCodes
            ->filter(fn ($code) => ! isset($existingCodes[$code]))
            ->values()
            ->all();
    }

    /**
     * Import columns expected:
     *   number | journal_date | reference | description | account |
     *   budget_code | cost_category | source_of_fund | debit | credit | vendor
     *
     * Rows are grouped by "reference" to form one JournalEntry per reference.
     * Each row becomes one JournalEntryLine.
     * journal_date is an Excel serial number → converted to date.
     *
     * Returns ['imported' => N, 'skipped' => N, 'errors' => [string]]
     */
    public function importJournalEntries(string $filePath, ?int $accountingPeriodId = null): array
    {
        $imported = 0;
        $skipped  = 0;
        $errors   = [];

        $userId   = Auth::id();
        $etbId    = Currency::where('code', 'ETB')->value('id');

        // Default to earliest open period if none specified
        if (! $accountingPeriodId) {
            $accountingPeriodId = AccountingPeriod::where('status', 'open')
                ->orderBy('start_date')
                ->value('id');
        }

        // Pre-build account lookup by code
        $accountByCode = ChartOfAccount::where('is_active', true)
            ->get()
            ->keyBy(fn ($a) => strtoupper(trim($a->code)));

        if ($accountByCode->isEmpty()) {
            return [
                'imported' => 0,
                'skipped'  => 0,
                'errors'   => ['⚠ Chart of Accounts is empty. Please import the Chart of Accounts first before importing Journal Entries.'],
            ];
        }

        // ── Pre-flight: abort if any account codes are missing ────────────
        $missingCodes = $this->missingAccountCodes($filePath);
        if (! empty($missingCodes)) {
            $list = implode(', ', $missingCodes);
            return [
                'imported' => 0,
                'skipped'  => 0,
                'errors'   => [
                    '❌ Import blocked — ' . count($missingCodes) . ' account code(s) not found in Chart of Accounts: ' . $list . '. '
                    . 'Please add these accounts to the CoA first, then re-import.',
                ],
            ];
        }

        try {
            $headers = $this->loadHeaders($filePath);
            $rows    = $this->loadRows($filePath);

            $colDate     = array_search('journal_date', $headers);
            $colRef      = array_search('reference', $headers);
            $colDesc     = array_search('description', $headers);
            $colAccount  = array_search('account', $headers);
            $colBudget   = array_search('budget_code', $headers);
            $colCategory = array_search('cost_category', $headers);
            $colDebit    = array_search('debit', $headers);
            $colCredit   = array_search('credit', $headers);

            // Group rows by reference number
            $grouped = $rows->groupBy(fn ($r) => trim((string) ($r[$colRef] ?? 'UNKNOWN')));

            // ── Pre-flight: balance check (Σ DR = Σ CR per reference) ─────────
            $unbalanced = [];
            foreach ($grouped as $reference => $lines) {
                if ($reference === '' || $reference === 'UNKNOWN') {
                    continue;
                }
                $totalDR = $lines->sum(fn ($r) => (float) ($r[$colDebit]  ?? 0));
                $totalCR = $lines->sum(fn ($r) => (float) ($r[$colCredit] ?? 0));
                $diff    = round(abs($totalDR - $totalCR), 2);
                if ($diff > 0.01) {
                    $unbalanced[] = sprintf(
                        '%s — DR %.2f ≠ CR %.2f (diff: %.2f)',
                        $reference, $totalDR, $totalCR, $diff
                    );
                }
            }

            if (! empty($unbalanced)) {
                return [
                    'imported' => 0,
                    'skipped'  => 0,
                    'errors'   => array_merge(
                        ['❌ Import blocked — ' . count($unbalanced) . ' unbalanced journal entry/entries (Debit ≠ Credit):'],
                        $unbalanced
                    ),
                ];
            }

            DB::beginTransaction();

            foreach ($grouped as $reference => $lines) {
                if ($reference === '' || $reference === 'UNKNOWN') {
                    $skipped++; // count groups, not rows
                    continue;
                }

                // Skip if JE with this reference already exists (including soft-deleted)
                $existing = JournalEntry::withTrashed()
                    ->where('reference_number', $reference)
                    ->first();

                if ($existing) {
                    if ($existing->trashed()) {
                        // Restore the soft-deleted record so it's usable again
                        $existing->restore();
                        // Reset status to draft so it can be processed normally
                        $existing->update(['status' => 'draft', 'notes' => 'Restored from re-import']);
                    }
                    $skipped++; // count groups, not rows
                    continue;
                }

                // Parse date from the first line (Excel serial or date string)
                /** @var array<int, mixed> $firstRow */
                $firstRow = (array) $lines->first();
                $rawDate  = $firstRow[$colDate] ?? null;
                try {
                    if (is_numeric($rawDate)) {
                        // Excel date serial
                        $transactionDate = Carbon::instance(
                            \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $rawDate)
                        )->toDateString();
                    } else {
                        $transactionDate = Carbon::parse($rawDate)->toDateString();
                    }
                } catch (Throwable) {
                    $transactionDate = now()->toDateString();
                }

                $description = trim((string) ($firstRow[$colDesc] ?? $reference));

                // Create JournalEntry
                $je = JournalEntry::create([
                    'reference_number'      => $reference,
                    'accounting_period_id'  => $accountingPeriodId,
                    'transaction_date'      => $transactionDate,
                    'description'           => $description,
                    'status'                => 'draft',
                    'source'                => 'manual',
                    'currency_id'           => $etbId,
                    'exchange_rate_to_base' => 1.000000,
                    'prepared_by'           => $userId,
                    'notes'                 => 'Imported from Excel',
                ]);

                // Create lines
                $lineErrors  = [];
                $linesCreated = 0;
                foreach ($lines as $lineRow) {
                    /** @var array<int, mixed> $lineRow */
                    $accountCode = strtoupper(trim((string) ($lineRow[$colAccount] ?? '')));
                    $debit       = (float) ($lineRow[$colDebit]  ?? 0);
                    $credit      = (float) ($lineRow[$colCredit] ?? 0);

                    if ($accountCode === '') {
                        $lineErrors[] = "Empty account code in reference {$reference} — line skipped.";
                        continue;
                    }

                    $account = $accountByCode[$accountCode] ?? null;
                    if (! $account) {
                        // Should not happen after pre-flight, but guard anyway
                        $lineErrors[] = "Account code [{$accountCode}] not found — line in {$reference} skipped.";
                        continue;
                    }

                    JournalEntryLine::create([
                        'journal_entry_id' => $je->id,
                        'account_id'       => $account->id,
                        'debit'            => $debit,
                        'credit'           => $credit,
                        'activity_code'    => trim((string) ($lineRow[$colBudget] ?? '')),
                        'narration'        => trim((string) ($lineRow[$colDesc]   ?? '')),
                    ]);
                    $linesCreated++;
                }

                // If zero lines were created, the JE is useless — remove it
                if ($linesCreated === 0) {
                    $je->forceDelete();
                    $skipped++;
                    array_push($errors, ...$lineErrors);
                    continue;
                }

                array_push($errors, ...$lineErrors);
                $imported++;
            }

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            $errors[] = 'Fatal: ' . $e->getMessage();
        }

        return compact('imported', 'skipped', 'errors');
    }

    // ── Budget Codes Import ────────────────────────────────────────────────

    /**
     * Import columns expected:
     *   code | description | cost_category
     *
     * Returns ['imported' => N, 'skipped' => N, 'errors' => [string]]
     */
    public function importBudgetCodes(string $filePath): array
    {
        $imported = 0;
        $skipped  = 0;
        $errors   = [];

        try {
            $headers = $this->loadHeaders($filePath);
            $rows    = $this->loadRows($filePath);

            $colCode        = array_search('code', $headers);
            $colDescription = array_search('description', $headers);
            $colCategory    = array_search('cost_category', $headers);

            if ($colCode === false) {
                return [
                    'imported' => 0,
                    'skipped' => 0,
                    'errors' => ['Missing required "code" column in import file.'],
                ];
            }

            DB::beginTransaction();

            foreach ($rows as $row) {
                /** @var array<int, mixed> $row */
                $code = strtoupper(trim((string) ($row[$colCode] ?? '')));
                $description = trim((string) ($row[$colDescription] ?? ''));
                $costCategory = trim((string) ($row[$colCategory] ?? ''));

                if ($code === '') {
                    $skipped++;
                    continue;
                }

                $existing = BudgetCode::query()->where('code', $code)->first();
                if ($existing) {
                    $existing->fill([
                        'description' => $description !== '' ? $description : $existing->description,
                        'cost_category' => $costCategory !== '' ? $costCategory : $existing->cost_category,
                        'is_active' => true,
                    ])->save();
                    $skipped++;
                    continue;
                }

                BudgetCode::query()->create([
                    'code' => $code,
                    'description' => $description !== '' ? $description : null,
                    'cost_category' => $costCategory !== '' ? $costCategory : null,
                    'is_active' => true,
                ]);
                $imported++;
            }

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            $errors[] = 'Fatal: ' . $e->getMessage();
        }

        return compact('imported', 'skipped', 'errors');
    }
}

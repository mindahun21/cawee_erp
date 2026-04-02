<?php

namespace App\Services\Finance;

use App\Models\Finance\ChartOfAccount;
use App\Models\Finance\GeneralLedger;
use App\Models\Finance\JournalEntry;
use App\Models\Finance\JournalEntryLine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * GeneralLedgerService
 *
 * Responsible for:
 *  • Posting approved JournalEntry records to the finance_general_ledgers table.
 *  • Computing and storing the running balance for each account on every post.
 *  • Providing balance-query helpers used by widgets, reports, and the CoA view.
 *  • Offering a full running-balance recalculation utility for account repair/audit.
 *
 * Design constraints:
 *  • The GL is append-only — rows are never soft-deleted or updated after insert.
 *  • Running balance is always expressed relative to the account's normal balance:
 *      Debit-normal  (asset, expense):   +debit − credit
 *      Credit-normal (liability, equity, income): −debit + credit
 *    A positive running_balance always means the account is "on its normal side".
 *  • All amounts in the GL are stored in the transaction currency; currency
 *    conversion to the functional currency (ETB) is handled at report generation.
 */
class GeneralLedgerService
{
    // ── Public API ────────────────────────────────────────────────────────────

    /**
     * Post all lines of a JournalEntry to the General Ledger.
     *
     * Pre-conditions (enforced by JournalEntryService before calling here):
     *  • $je->status === 'posted'
     *  • The JE is balanced (sum(debit) === sum(credit))
     *  • The accounting period is open
     *
     * @throws \RuntimeException if a GL row already exists for any line (duplicate post guard)
     */
    public function postJournalEntry(JournalEntry $je): void
    {
        $je->loadMissing(['lines', 'lines.account', 'lines.account.accountType']);

        if ($je->lines->isEmpty()) {
            throw new \RuntimeException(
                "Cannot post journal entry [{$je->reference_number}]: it has no lines."
            );
        }

        DB::transaction(function () use ($je) {
            foreach ($je->lines as $line) {
                $this->postLine($line, $je);
            }
        });
    }

    /**
     * Return the running balance for an account as at a given date.
     *
     * Looks up the most recent GL row for $accountId whose transaction_date
     * is ≤ $asOf, ordered by (transaction_date DESC, id DESC) so that multiple
     * postings on the same date are handled correctly.
     *
     * Returns 0.0 when the account has no GL history up to that date.
     */
    public function getRunningBalance(int $accountId, \DateTimeInterface|string $asOf): float
    {
        $date = $asOf instanceof \DateTimeInterface
            ? $asOf->format('Y-m-d')
            : $asOf;

        return (float) GeneralLedger::where('account_id', $accountId)
            ->where('transaction_date', '<=', $date)
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->value('running_balance') ?? 0.0;
    }

    /**
     * Return the latest running balance for an account (all-time).
     */
    public function getAccountBalance(int $accountId): float
    {
        return (float) GeneralLedger::where('account_id', $accountId)
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->value('running_balance') ?? 0.0;
    }

    /**
     * Return a map of account_id → current balance for a set of account IDs.
     * More efficient than calling getAccountBalance() in a loop.
     *
     * @param  int[]  $accountIds
     * @return array<int, float>  [account_id => balance]
     */
    public function getBalancesForAccounts(array $accountIds): array
    {
        if (empty($accountIds)) {
            return [];
        }

        // Subquery: for each account, get the id of its most recent GL row
        $latestIds = GeneralLedger::selectRaw('MAX(id) as id')
            ->whereIn('account_id', $accountIds)
            ->groupBy('account_id')
            ->pluck('id');

        return GeneralLedger::whereIn('id', $latestIds)
            ->pluck('running_balance', 'account_id')
            ->map(fn ($v) => (float) $v)
            ->toArray();
    }

    /**
     * Return trial-balance data for a given accounting period.
     *
     * Result shape per account:
     *   [
     *     'account_id'      => int,
     *     'code'            => string,
     *     'name'            => string,
     *     'opening_balance' => float,
     *     'total_debit'     => float,
     *     'total_credit'    => float,
     *     'closing_balance' => float,
     *   ]
     *
     * @return \Illuminate\Support\Collection
     */
    public function getTrialBalance(int $periodId): \Illuminate\Support\Collection
    {
        // Get the period's date range
        $period = \App\Models\Finance\AccountingPeriod::findOrFail($periodId);

        // Opening balance = running_balance of the last GL entry before period start
        $openingBalances = GeneralLedger::selectRaw('account_id, MAX(id) as last_id')
            ->where('transaction_date', '<', $period->start_date)
            ->groupBy('account_id')
            ->get()
            ->mapWithKeys(function ($row) {
                $balance = GeneralLedger::find($row->last_id)?->running_balance ?? 0;
                return [$row->account_id => (float) $balance];
            });

        // Period movements
        $movements = GeneralLedger::selectRaw(
                'account_id,
                 SUM(debit)  as total_debit,
                 SUM(credit) as total_credit'
            )
            ->where('period_id', $periodId)
            ->groupBy('account_id')
            ->get()
            ->keyBy('account_id');

        // Closing balance = latest running_balance within the period
        $closingBalances = GeneralLedger::selectRaw('account_id, MAX(id) as last_id')
            ->where('period_id', $periodId)
            ->groupBy('account_id')
            ->get()
            ->mapWithKeys(function ($row) {
                $balance = GeneralLedger::find($row->last_id)?->running_balance ?? 0;
                return [$row->account_id => (float) $balance];
            });

        // Merge and attach account details
        $accountIds = collect($openingBalances->keys())
            ->merge($movements->keys())
            ->unique()
            ->values();

        $accounts = ChartOfAccount::whereIn('id', $accountIds)
            ->where('is_header', false)
            ->orderBy('code')
            ->get()
            ->keyBy('id');

        return $accountIds
            ->filter(fn ($id) => isset($accounts[$id]))
            ->map(function ($id) use ($accounts, $openingBalances, $movements, $closingBalances) {
                $account  = $accounts[$id];
                $movement = $movements[$id] ?? null;

                return [
                    'account_id'      => $id,
                    'code'            => $account->code,
                    'name'            => $account->name,
                    'opening_balance' => $openingBalances[$id] ?? 0.0,
                    'total_debit'     => $movement ? (float) $movement->total_debit  : 0.0,
                    'total_credit'    => $movement ? (float) $movement->total_credit : 0.0,
                    'closing_balance' => $closingBalances[$id] ?? ($openingBalances[$id] ?? 0.0),
                ];
            });
    }

    /**
     * Fully rebuild the running_balance column for a single account.
     *
     * Use this after:
     *  • Manually correcting GL data (admin only)
     *  • Detecting running-balance inconsistency during an audit
     *
     * All GL rows for the account are read in (transaction_date, id) order,
     * and running_balance is recomputed from scratch using the account's
     * normal-balance direction.
     *
     * This method wraps the update in a DB transaction and logs a warning
     * so that any recalculation is traceable.
     */
    public function recalculateRunningBalance(int $accountId): void
    {
        $account = ChartOfAccount::with('accountType')->findOrFail($accountId);

        $normalBalance = $account->accountType?->normal_balance ?? 'debit';

        Log::warning('GeneralLedgerService: recalculateRunningBalance triggered', [
            'account_id'     => $accountId,
            'account_code'   => $account->code,
            'normal_balance' => $normalBalance,
            'triggered_by'   => auth()->id() ?? 'system',
        ]);

        DB::transaction(function () use ($accountId, $normalBalance) {
            $entries = GeneralLedger::where('account_id', $accountId)
                ->orderBy('transaction_date')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $running = 0.0;

            foreach ($entries as $entry) {
                if ($normalBalance === 'debit') {
                    $running += (float) $entry->debit - (float) $entry->credit;
                } else {
                    $running += (float) $entry->credit - (float) $entry->debit;
                }

                // Use query builder to avoid triggering model events on the GL
                GeneralLedger::where('id', $entry->id)
                    ->update(['running_balance' => round($running, 2)]);
            }
        });
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Post a single JournalEntryLine to the GL table.
     */
    private function postLine(JournalEntryLine $line, JournalEntry $je): void
    {
        // Duplicate-post guard: each JE line should produce exactly one GL row
        if (GeneralLedger::where('journal_entry_line_id', $line->id)->exists()) {
            throw new \RuntimeException(
                "GL posting conflict: a General Ledger row already exists for " .
                "JournalEntryLine ID [{$line->id}] (JE: {$je->reference_number}). " .
                "This entry may have already been posted."
            );
        }

        $newBalance = $this->computeNewRunningBalance($line, $je->transaction_date);

        GeneralLedger::create([
            'account_id'            => $line->account_id,
            'journal_entry_line_id' => $line->id,
            'transaction_date'      => $je->transaction_date,
            'period_id'             => $je->accounting_period_id,
            'debit'                 => $line->debit,
            'credit'                => $line->credit,
            'running_balance'       => round($newBalance, 2),
            'currency_id'           => $je->currency_id,
        ]);
    }

    /**
     * Compute the new running balance for an account after a line is posted.
     *
     * We look up the most recent GL row for this account whose
     * (transaction_date, id) is strictly prior to the current posting,
     * then apply the debit/credit delta in the correct direction.
     *
     * Note: rows posted on the same date are ordered by insertion id, so
     * the running balance is always consistent within a batch post.
     */
    private function computeNewRunningBalance(JournalEntryLine $line, $transactionDate): float
    {
        // Latest GL row for this account up to (and including) this date
        // We use the raw id ordering within the same date because we've already
        // inserted previous lines in this transaction via postJournalEntry().
        $previousBalance = (float) GeneralLedger::where('account_id', $line->account_id)
            ->where(function ($query) use ($transactionDate) {
                $query->where('transaction_date', '<', $transactionDate)
                      ->orWhere('transaction_date', '=', $transactionDate);
            })
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->value('running_balance') ?? 0.0;

        $normalBalance = $line->account->accountType?->normal_balance ?? 'debit';

        if ($normalBalance === 'debit') {
            // Asset / Expense: debits increase, credits decrease the balance
            return $previousBalance + (float) $line->debit - (float) $line->credit;
        }

        // Liability / Equity / Income: credits increase, debits decrease the balance
        return $previousBalance + (float) $line->credit - (float) $line->debit;
    }
}

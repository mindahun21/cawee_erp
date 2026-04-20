<?php

namespace App\Services\Finance;

use App\Models\Finance\AccountingPeriod;
use App\Models\Finance\BankAccount;
use App\Models\Finance\CashReceiptVoucher;
use App\Models\Finance\FinanceAuditLog;
use App\Models\Finance\FinanceSetting;
use App\Models\Finance\PaymentVoucher;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * VoucherService
 *
 * Handles the full lifecycle of Cash Receipt Vouchers (CRV) and
 * Payment Vouchers (PV):
 *   • Auto-reference generation   (CRV-2026-0001 / PV-2026-0001)
 *   • Tax computation              (WHT, VAT)
 *   • Status transitions           (submit → approve → post)
 *   • GL auto-posting              (delegates to JournalEntryService)
 *   • Bank balance synchronisation (updates BankAccount.current_balance on post)
 *
 * All state-changing methods are DB-transaction wrapped and emit
 * immutable FinanceAuditLog entries.
 */
class VoucherService
{
    public function __construct(
        private readonly JournalEntryService $jeService,
        private readonly GeneralLedgerService $glService,
    ) {}

    // ─────────────────────────────────────────────────────────────────
    // Reference Number Generation
    // ─────────────────────────────────────────────────────────────────

    public function generateCrvReference(int $year = null): string
    {
        return $this->generateReference(
            CashReceiptVoucher::class,
            'crv_number',
            FinanceSetting::get('crv_number_prefix', 'CRV'),
            $year
        );
    }

    public function generatePvReference(int $year = null): string
    {
        return $this->generateReference(
            PaymentVoucher::class,
            'pv_number',
            FinanceSetting::get('pv_number_prefix', 'PV'),
            $year
        );
    }

    private function generateReference(string $model, string $field, string $prefix, ?int $year): string
    {
        $year = $year ?? now()->year;
        $like = "{$prefix}-{$year}-%";

        $lastRef = $model::withTrashed()
            ->where($field, 'like', $like)
            ->orderByRaw("LENGTH({$field}) DESC")
            ->orderBy($field, 'desc')
            ->value($field);

        $sequence = 1;

        if ($lastRef) {
            $parts    = explode('-', $lastRef);
            $sequence = ((int) end($parts)) + 1;
        }

        return sprintf('%s-%d-%04d', $prefix, $year, $sequence);
    }

    // ─────────────────────────────────────────────────────────────────
    // Tax Computation
    // ─────────────────────────────────────────────────────────────────

    /**
     * Compute WHT and VAT amounts for a Payment Voucher.
     *
     * @return array{wht_amount: float, vat_amount: float, net_amount: float}
     */
    public function computeTaxes(
        float  $grossAmount,
        float  $whtRate,
        string $vatType,
        float  $vatRate,
    ): array {
        $whtAmount  = round($grossAmount * $whtRate, 2);
        $vatAmount  = in_array($vatType, ['collected', 'payable'])
            ? round($grossAmount * $vatRate, 2)
            : 0.0;
        $netAmount  = round($grossAmount - $whtAmount, 2);

        return [
            'wht_amount' => $whtAmount,
            'vat_amount' => $vatAmount,
            'net_amount' => $netAmount,
        ];
    }

    // ─────────────────────────────────────────────────────────────────
    // CRV Lifecycle
    // ─────────────────────────────────────────────────────────────────

    /**
     * Submit a draft CRV for approval.
     */
    public function submitCrv(CashReceiptVoucher $crv, User $by): void
    {
        if (! $crv->isDraft()) {
            throw new \RuntimeException("Only draft CRVs can be submitted. Status: {$crv->status}.");
        }

        DB::transaction(function () use ($crv, $by) {
            $old = $crv->status;
            $crv->forceFill([
                'status'      => 'pending_approval',
                'prepared_by' => $by->id,
            ])->save();

            FinanceAuditLog::record('approve', $crv,
                ['status' => $old],
                ['status' => 'pending_approval', 'submitted_by' => $by->id]
            );
        });
    }

    /**
     * Approve a pending CRV.
     */
    public function approveCrv(CashReceiptVoucher $crv, User $by, string $comments = ''): void
    {
        if ($crv->status !== 'pending_approval') {
            throw new \RuntimeException("CRV is not pending approval. Status: {$crv->status}.");
        }

        DB::transaction(function () use ($crv, $by, $comments) {
            $old = $crv->status;
            $crv->forceFill([
                'status'      => 'approved',
                'approved_by' => $by->id,
                'approved_at' => now(),
            ])->save();

            FinanceAuditLog::record('approve', $crv,
                ['status' => $old],
                ['status' => 'approved', 'approved_by' => $by->id, 'comments' => $comments]
            );
        });
    }

    /**
     * Post an approved CRV to the GL and update the bank balance.
     *
     * JE pattern:
     *   DR: Bank Account (or Cash on Hand)
     *   CR: Income / Donation revenue account
     */
    public function postCrv(CashReceiptVoucher $crv, User $by): void
    {
        if ($crv->status !== 'approved') {
            throw new \RuntimeException("Only approved CRVs can be posted. Status: {$crv->status}.");
        }

        $period = AccountingPeriod::find($crv->accounting_period_id);
        if (! $period || $period->status !== 'open') {
            throw new \RuntimeException('The accounting period is not open.');
        }

        $crv->loadMissing('bankAccount.chartOfAccount');

        DB::transaction(function () use ($crv, $by, $period) {

            // ── Build the double-entry lines ───────────────────────────────
            $bankCoaId    = $crv->bankAccount?->chart_of_account_id
                ?? FinanceSetting::get('default_cash_account_id');
            $incomeCoaId  = $this->resolveIncomeAccount($crv->income_type);

            $jeRef = $this->jeService->generateReference(now()->year);

            $je = \App\Models\Finance\JournalEntry::create([
                'reference_number'      => $jeRef,
                'accounting_period_id'  => $crv->accounting_period_id,
                'transaction_date'      => $crv->receipt_date,
                'description'           => "CRV {$crv->crv_number} — {$crv->received_from}",
                'status'                => 'approved',
                'source'                => 'crv',
                'source_type'           => CashReceiptVoucher::class,
                'source_id'             => $crv->id,
                'prepared_by'           => $by->id,
                'approved_by'           => $by->id,
                'currency_id'           => $crv->currency_id,
                'exchange_rate_to_base' => $crv->exchange_rate_to_base,
            ]);

            // DR: Bank / Cash account
            \App\Models\Finance\JournalEntryLine::create([
                'journal_entry_id' => $je->id,
                'account_id'       => $bankCoaId,
                'debit'            => $crv->amount_in_base,
                'credit'           => 0,
                'cost_center_id'   => $crv->cost_center_id,
                'donor_id'         => $crv->donor_id,
                'project_id'       => $crv->project_id,
                'activity_code'    => $crv->activity_code,
                'narration'        => "Receipt from {$crv->received_from}",
            ]);

            // CR: Income account
            \App\Models\Finance\JournalEntryLine::create([
                'journal_entry_id' => $je->id,
                'account_id'       => $incomeCoaId,
                'debit'            => 0,
                'credit'           => $crv->amount_in_base,
                'cost_center_id'   => $crv->cost_center_id,
                'donor_id'         => $crv->donor_id,
                'project_id'       => $crv->project_id,
                'activity_code'    => $crv->activity_code,
                'narration'        => "Income: {$crv->income_type} — {$crv->received_from}",
            ]);

            $je->load('lines');
            $this->glService->postJournalEntry($je);
            $je->forceFill(['status' => 'posted', 'posted_at' => now()])->save();

            // ── Update bank account running balance ────────────────────────
            if ($crv->bank_account_id) {
                BankAccount::where('id', $crv->bank_account_id)
                    ->increment('current_balance', (float) $crv->amount_in_base);
            }

            $crv->forceFill([
                'status'           => 'posted',
                'journal_entry_id' => $je->id,
                'posted_at'        => now(),
            ])->save();

            FinanceAuditLog::record('post', $crv,
                ['status' => 'approved'],
                ['status' => 'posted', 'je_ref' => $jeRef, 'posted_by' => $by->id]
            );
        });
    }

    // ─────────────────────────────────────────────────────────────────
    // PV Lifecycle
    // ─────────────────────────────────────────────────────────────────

    /**
     * Submit a draft PV for approval.
     */
    public function submitPv(PaymentVoucher $pv, User $by): void
    {
        if (! $pv->isDraft()) {
            throw new \RuntimeException("Only draft PVs can be submitted. Status: {$pv->status}.");
        }

        DB::transaction(function () use ($pv, $by) {
            $old = $pv->status;
            $pv->forceFill([
                'status'      => 'pending_approval',
                'prepared_by' => $by->id,
            ])->save();

            FinanceAuditLog::record('approve', $pv,
                ['status' => $old],
                ['status' => 'pending_approval', 'submitted_by' => $by->id]
            );
        });
    }

    /**
     * Approve a pending PV.
     */
    public function approvePv(PaymentVoucher $pv, User $by, string $comments = ''): void
    {
        if ($pv->status !== 'pending_approval') {
            throw new \RuntimeException("PV is not pending approval. Status: {$pv->status}.");
        }

        DB::transaction(function () use ($pv, $by, $comments) {
            $old = $pv->status;
            $pv->forceFill([
                'status'      => 'approved',
                'approved_by' => $by->id,
                'approved_at' => now(),
            ])->save();

            FinanceAuditLog::record('approve', $pv,
                ['status' => $old],
                ['status' => 'approved', 'approved_by' => $by->id, 'comments' => $comments]
            );
        });
    }

    /**
     * Post an approved PV to the GL.
     *
     * JE pattern:
     *   DR: Expense Account (gross amount)
     *   CR: Bank Account    (net amount — cash actually going out)
     *   CR: WHT Payable     (withholding tax deducted)
     *   CR: VAT Payable     (if applicable)
     */
    public function postPv(PaymentVoucher $pv, User $by): void
    {
        if ($pv->status !== 'approved') {
            throw new \RuntimeException("Only approved PVs can be posted. Status: {$pv->status}.");
        }

        $period = AccountingPeriod::find($pv->accounting_period_id);
        if (! $period || $period->status !== 'open') {
            throw new \RuntimeException('The accounting period is not open.');
        }

        $pv->loadMissing('bankAccount.chartOfAccount');

        DB::transaction(function () use ($pv, $by) {

            $bankCoaId    = $pv->bankAccount?->chart_of_account_id
                ?? FinanceSetting::get('default_cash_account_id');
            $whtCoaId     = FinanceSetting::get('wht_payable_account_id');
            $vatCoaId     = FinanceSetting::get('vat_payable_account_id');

            // We need an expense account — in the PV form the user picks it;
            // fall back to the generic expense placeholder for now.
            $expenseCoaId = FinanceSetting::get('default_expense_account_id');

            $jeRef = $this->jeService->generateReference(now()->year);

            $je = \App\Models\Finance\JournalEntry::create([
                'reference_number'      => $jeRef,
                'accounting_period_id'  => $pv->accounting_period_id,
                'transaction_date'      => $pv->payment_date,
                'description'           => "PV {$pv->pv_number} — {$pv->payee_name}",
                'status'                => 'approved',
                'source'                => 'pv',
                'source_type'           => PaymentVoucher::class,
                'source_id'             => $pv->id,
                'prepared_by'           => $by->id,
                'approved_by'           => $by->id,
                'currency_id'           => $pv->currency_id,
                'exchange_rate_to_base' => $pv->exchange_rate_to_base,
            ]);

            $gross  = (float) $pv->gross_amount;
            $wht    = (float) $pv->withholding_tax_amount;
            $vat    = (float) $pv->vat_amount;
            $net    = (float) $pv->net_amount;

            $dims = [
                'cost_center_id' => $pv->cost_center_id,
                'donor_id'       => $pv->donor_id,
                'project_id'     => $pv->project_id,
                'activity_code'  => $pv->activity_code,
            ];

            // DR: Expense
            \App\Models\Finance\JournalEntryLine::create(array_merge($dims, [
                'journal_entry_id' => $je->id,
                'account_id'       => $expenseCoaId,
                'debit'            => $gross,
                'credit'           => 0,
                'narration'        => "Expense: {$pv->payee_name}",
            ]));

            // CR: Bank (net)
            \App\Models\Finance\JournalEntryLine::create(array_merge($dims, [
                'journal_entry_id' => $je->id,
                'account_id'       => $bankCoaId,
                'debit'            => 0,
                'credit'           => $net,
                'narration'        => "Payment to {$pv->payee_name} net of WHT",
            ]));

            // CR: WHT Payable
            if ($wht > 0 && $whtCoaId) {
                \App\Models\Finance\JournalEntryLine::create(array_merge($dims, [
                    'journal_entry_id' => $je->id,
                    'account_id'       => $whtCoaId,
                    'debit'            => 0,
                    'credit'           => $wht,
                    'narration'        => "WHT withheld from {$pv->payee_name}",
                ]));
            }

            // CR: VAT Payable
            if ($vat > 0 && $vatCoaId) {
                \App\Models\Finance\JournalEntryLine::create(array_merge($dims, [
                    'journal_entry_id' => $je->id,
                    'account_id'       => $vatCoaId,
                    'debit'            => 0,
                    'credit'           => $vat,
                    'narration'        => "VAT — {$pv->vat_type}",
                ]));
            }

            $je->load('lines');
            $this->glService->postJournalEntry($je);
            $je->forceFill(['status' => 'posted', 'posted_at' => now()])->save();

            // ── Update bank balance ────────────────────────────────────────
            if ($pv->bank_account_id) {
                BankAccount::where('id', $pv->bank_account_id)
                    ->decrement('current_balance', $net);
            }

            $pv->forceFill([
                'status'           => 'posted',
                'journal_entry_id' => $je->id,
                'posted_at'        => now(),
            ])->save();

            FinanceAuditLog::record('post', $pv,
                ['status' => 'approved'],
                ['status' => 'posted', 'je_ref' => $jeRef, 'posted_by' => $by->id]
            );
        });
    }

    // ─────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────

    /**
     * Resolve income account from income_type using Finance Settings,
     * falling back to a generic income account.
     */
    private function resolveIncomeAccount(string $incomeType): int
    {
        $map = [
            'grant'    => FinanceSetting::get('grant_income_account_id'),
            'donation' => FinanceSetting::get('donation_income_account_id'),
            'service'  => FinanceSetting::get('service_income_account_id'),
            'interest' => FinanceSetting::get('interest_income_account_id'),
        ];

        return (int) ($map[$incomeType]
            ?? FinanceSetting::get('default_income_account_id')
            ?? 1);
    }
}

<?php

namespace App\Services\Finance;

use App\Models\Finance\AccountingPeriod;
use App\Models\Finance\BankAccount;
use App\Models\Finance\FinanceAuditLog;
use App\Models\Finance\FinanceSetting;
use App\Models\Finance\PettyCashFund;
use App\Models\Finance\PettyCashPayment;
use App\Models\Finance\PettyCashReplenishment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * PettyCashService
 *
 * Manages the full petty cash lifecycle:
 *   • Payment approval → deducts from fund balance
 *   • Replenishment request → approval → disbursement → GL posting
 *                             → refills fund balance
 *   • Auto-reference generation
 *
 * GL posting pattern for replenishment:
 *   DR: Petty Cash Fund Account (the fund's GL account)
 *   CR: Bank Account            (the source bank account)
 */
class PettyCashService
{
    public function __construct(
        private readonly JournalEntryService $jeService,
        private readonly GeneralLedgerService $glService,
    ) {}

    // ─────────────────────────────────────────────────────────────────
    // Reference Number Generation
    // ─────────────────────────────────────────────────────────────────

    public function generatePaymentNumber(int $year = null): string
    {
        $year   = $year ?? now()->year;
        $prefix = FinanceSetting::get('pcp_number_prefix', 'PCP');
        $like   = "{$prefix}-{$year}-%";

        $lastRef = PettyCashPayment::withTrashed()
            ->where('payment_number', 'like', $like)
            ->orderByRaw('LENGTH(payment_number) DESC')
            ->orderBy('payment_number', 'desc')
            ->value('payment_number');

        $sequence = $lastRef ? ((int) last(explode('-', $lastRef))) + 1 : 1;
        return sprintf('%s-%d-%04d', $prefix, $year, $sequence);
    }

    public function generateReplenishmentNumber(int $year = null): string
    {
        $year   = $year ?? now()->year;
        $prefix = FinanceSetting::get('pcr_number_prefix', 'PCR');
        $like   = "{$prefix}-{$year}-%";

        $lastRef = PettyCashReplenishment::withTrashed()
            ->where('replenishment_number', 'like', $like)
            ->orderByRaw('LENGTH(replenishment_number) DESC')
            ->orderBy('replenishment_number', 'desc')
            ->value('replenishment_number');

        $sequence = $lastRef ? ((int) last(explode('-', $lastRef))) + 1 : 1;
        return sprintf('%s-%d-%04d', $prefix, $year, $sequence);
    }

    // ─────────────────────────────────────────────────────────────────
    // Payment Lifecycle
    // ─────────────────────────────────────────────────────────────────

    /**
     * Approve a pending petty cash payment.
     *
     * Deducts the amount from the fund's current_balance.
     *
     * @throws \RuntimeException if fund balance is insufficient
     */
    public function approvePayment(PettyCashPayment $payment, User $by): void
    {
        if (! $payment->isPending()) {
            throw new \RuntimeException("Payment is not pending approval. Status: {$payment->status}.");
        }

        $fund = $payment->fund;

        if ((float) $fund->current_balance < (float) $payment->amount) {
            throw new \RuntimeException(
                sprintf(
                    'Insufficient fund balance. Available: %s | Requested: %s',
                    number_format($fund->current_balance, 2),
                    number_format($payment->amount, 2)
                )
            );
        }

        DB::transaction(function () use ($payment, $by, $fund) {
            $fund->decrement('current_balance', (float) $payment->amount);

            $payment->forceFill([
                'status'      => 'approved',
                'approved_by' => $by->id,
                'approved_at' => now(),
            ])->save();

            FinanceAuditLog::record('approve', $payment,
                ['status' => 'pending'],
                [
                    'status'               => 'approved',
                    'approved_by'          => $by->id,
                    'fund_balance_after'   => $fund->fresh()->current_balance,
                ]
            );
        });
    }

    /**
     * Reject a pending petty cash payment.
     */
    public function rejectPayment(PettyCashPayment $payment, User $by, string $reason = ''): void
    {
        if (! $payment->isPending()) {
            throw new \RuntimeException("Payment is not pending. Status: {$payment->status}.");
        }

        DB::transaction(function () use ($payment, $by, $reason) {
            $payment->forceFill([
                'status'      => 'rejected',
                'approved_by' => $by->id,
                'approved_at' => now(),
            ])->save();

            FinanceAuditLog::record('reject', $payment,
                ['status' => 'pending'],
                ['status' => 'rejected', 'rejected_by' => $by->id, 'reason' => $reason]
            );
        });
    }

    // ─────────────────────────────────────────────────────────────────
    // Replenishment Lifecycle
    // ─────────────────────────────────────────────────────────────────

    /**
     * Submit a draft replenishment for approval.
     */
    public function submitReplenishment(PettyCashReplenishment $replenishment, User $by): void
    {
        if (! $replenishment->isDraft()) {
            throw new \RuntimeException("Only draft replenishments can be submitted.");
        }

        DB::transaction(function () use ($replenishment, $by) {
            $replenishment->forceFill([
                'status'       => 'pending',
                'requested_by' => $by->id,
            ])->save();

            FinanceAuditLog::record('approve', $replenishment,
                ['status' => 'draft'],
                ['status' => 'pending', 'submitted_by' => $by->id]
            );
        });
    }

    /**
     * Approve a pending replenishment request.
     */
    public function approveReplenishment(
        PettyCashReplenishment $replenishment,
        User $by,
        float $approvedAmount,
        string $comments = '',
    ): void {
        if (! $replenishment->isPending()) {
            throw new \RuntimeException("Only pending replenishments can be approved.");
        }

        DB::transaction(function () use ($replenishment, $by, $approvedAmount, $comments) {
            $replenishment->forceFill([
                'status'          => 'approved',
                'amount_approved' => $approvedAmount,
                'approved_by'     => $by->id,
                'approved_at'     => now(),
            ])->save();

            FinanceAuditLog::record('approve', $replenishment,
                ['status' => 'pending'],
                [
                    'status'          => 'approved',
                    'amount_approved' => $approvedAmount,
                    'approved_by'     => $by->id,
                    'comments'        => $comments,
                ]
            );
        });
    }

    /**
     * Disburse an approved replenishment.
     *
     * Generates and posts the GL journal entry, then tops up the
     * fund's current_balance by the approved amount.
     *
     * JE:
     *   DR: Petty Cash Fund GL account
     *   CR: Bank Account
     */
    public function disburseReplenishment(PettyCashReplenishment $replenishment, User $by): void
    {
        if (! $replenishment->isApproved()) {
            throw new \RuntimeException("Only approved replenishments can be disbursed.");
        }

        $period = AccountingPeriod::find($replenishment->accounting_period_id);
        if (! $period || $period->status !== 'open') {
            throw new \RuntimeException('The accounting period is not open.');
        }

        $replenishment->loadMissing(['fund.chartOfAccount', 'bankAccount.chartOfAccount']);

        DB::transaction(function () use ($replenishment, $by) {
            $fund           = $replenishment->fund;
            $fundCoaId      = $fund->chart_of_account_id
                ?? FinanceSetting::get('default_petty_cash_account_id');
            $bankCoaId      = $replenishment->bankAccount?->chart_of_account_id
                ?? FinanceSetting::get('default_bank_account_id');
            $amount         = (float) $replenishment->amount_approved;

            $jeRef = $this->jeService->generateReference(now()->year);

            $je = \App\Models\Finance\JournalEntry::create([
                'reference_number'      => $jeRef,
                'accounting_period_id'  => $replenishment->accounting_period_id,
                'transaction_date'      => now()->toDateString(),
                'description'           => "PCR {$replenishment->replenishment_number} — {$fund->fund_name} replenishment",
                'status'                => 'approved',
                'source'                => 'petty_cash',
                'source_type'           => PettyCashReplenishment::class,
                'source_id'             => $replenishment->id,
                'prepared_by'           => $by->id,
                'approved_by'           => $by->id,
                'currency_id'           => $fund->currency_id,
                'exchange_rate_to_base' => 1,
            ]);

            // DR: Petty Cash Fund account
            \App\Models\Finance\JournalEntryLine::create([
                'journal_entry_id' => $je->id,
                'account_id'       => $fundCoaId,
                'debit'            => $amount,
                'credit'           => 0,
                'cost_center_id'   => $fund->cost_center_id,
                'narration'        => "Replenishment of {$fund->fund_name}",
            ]);

            // CR: Bank Account
            \App\Models\Finance\JournalEntryLine::create([
                'journal_entry_id' => $je->id,
                'account_id'       => $bankCoaId,
                'debit'            => 0,
                'credit'           => $amount,
                'cost_center_id'   => $fund->cost_center_id,
                'narration'        => "Transfer to petty cash fund {$fund->fund_code}",
            ]);

            $je->load('lines');
            $this->glService->postJournalEntry($je);
            $je->forceFill(['status' => 'posted', 'posted_at' => now()])->save();

            // Top up the fund balance
            $fund->increment('current_balance', $amount);

            // Update bank balance
            if ($replenishment->bank_account_id) {
                BankAccount::where('id', $replenishment->bank_account_id)
                    ->decrement('current_balance', $amount);
            }

            $replenishment->forceFill([
                'status'           => 'disbursed',
                'journal_entry_id' => $je->id,
                'disbursed_by'     => $by->id,
                'disbursed_at'     => now(),
            ])->save();

            FinanceAuditLog::record('post', $replenishment,
                ['status' => 'approved'],
                ['status' => 'disbursed', 'je_ref' => $jeRef, 'disbursed_by' => $by->id]
            );
        });
    }
}

<?php

namespace App\Services\Finance;

use App\Models\Finance\AccountingPeriod;
use App\Models\Finance\FinanceAuditLog;
use App\Models\Finance\FinanceSetting;
use App\Models\Finance\JournalEntry;
use App\Models\Finance\JournalEntryLine;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * JournalEntryService
 *
 * Central service for all JournalEntry lifecycle operations:
 *   • Auto-reference generation  (JE-2026-0001)
 *   • Balance validation          (Σdebit == Σcredit)
 *   • Status transitions          (submit → approve → post)
 *   • GL posting                  (delegates to GeneralLedgerService)
 *   • Reversal                    (creates mirror JE, marks original as reversed)
 *
 * All mutation methods are wrapped in DB transactions and record
 * an immutable FinanceAuditLog entry on every state change.
 */
class JournalEntryService
{
    public function __construct(
        private readonly GeneralLedgerService $glService,
    ) {}

    // ─────────────────────────────────────────────────────────────────
    // Reference Number Generation
    // ─────────────────────────────────────────────────────────────────

    /**
     * Generate the next sequential reference number for a journal entry.
     *
     * Format: {PREFIX}-{YEAR}-{SEQUENCE:04d}
     * Example: JE-2026-0001
     *
     * Uses a pessimistic string-based max scan so it works across any DB
     * without relying on auto-increment gaps.
     */
    public function generateReference(int $year = null): string
    {
        $year   = $year ?? now()->year;
        $prefix = FinanceSetting::get('je_number_prefix', 'JE');
        $like   = "{$prefix}-{$year}-%";

        // withTrashed ensures soft-deleted JEs don't cause duplicate numbers
        $lastRef = JournalEntry::withTrashed()
            ->where('reference_number', 'like', $like)
            ->orderByRaw('LENGTH(reference_number) DESC')
            ->orderBy('reference_number', 'desc')
            ->value('reference_number');

        $sequence = 1;

        if ($lastRef) {
            $parts    = explode('-', $lastRef);
            $sequence = ((int) end($parts)) + 1;
        }

        return sprintf('%s-%d-%04d', $prefix, $year, $sequence);
    }

    // ─────────────────────────────────────────────────────────────────
    // Validation
    // ─────────────────────────────────────────────────────────────────

    /**
     * Validate that the journal entry lines balance (Σdebit == Σcredit).
     *
     * Also validates:
     *   • At least 2 lines exist (double-entry requirement)
     *   • No line has both debit and credit > 0 simultaneously
     *   • No line has both debit and credit = 0 (empty line)
     *
     * @throws \InvalidArgumentException on any validation failure
     */
    public function validateBalance(JournalEntry $je): void
    {
        $je->loadMissing('lines');

        if ($je->lines->count() < 2) {
            throw new \InvalidArgumentException(
                'A journal entry must contain at least two lines (double-entry bookkeeping).'
            );
        }

        foreach ($je->lines as $index => $line) {
            $lineNum = $index + 1;
            $debit   = (float) $line->debit;
            $credit  = (float) $line->credit;

            if ($debit > 0 && $credit > 0) {
                throw new \InvalidArgumentException(
                    "Line {$lineNum} has both a debit ({$debit}) and a credit ({$credit}). " .
                    'A line may only have one side populated.'
                );
            }

            if ($debit <= 0 && $credit <= 0) {
                throw new \InvalidArgumentException(
                    "Line {$lineNum} has zero amounts on both sides. Remove empty lines before posting."
                );
            }
        }

        $totalDebit  = $je->lines->sum(fn ($l) => (float) $l->debit);
        $totalCredit = $je->lines->sum(fn ($l) => (float) $l->credit);

        if (abs($totalDebit - $totalCredit) > 0.005) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Journal entry is not balanced. ' .
                    'Total Debit: %s | Total Credit: %s | Difference: %s',
                    number_format($totalDebit, 2),
                    number_format($totalCredit, 2),
                    number_format(abs($totalDebit - $totalCredit), 2)
                )
            );
        }
    }

    /**
     * Validate that the journal entry's accounting period is open.
     *
     * @throws \RuntimeException if the period is closed or locked
     */
    public function validatePeriod(JournalEntry $je): void
    {
        $period = AccountingPeriod::find($je->accounting_period_id);

        if (! $period) {
            throw new \RuntimeException('The selected accounting period does not exist.');
        }

        if ($period->status !== 'open') {
            throw new \RuntimeException(
                "Accounting period \"{$period->name}\" is {$period->status}. " .
                'Only open periods accept new postings.'
            );
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // Status Transitions
    // ─────────────────────────────────────────────────────────────────

    /**
     * Submit a draft journal entry for approval.
     * Moves status: draft → pending_approval
     */
    public function submit(JournalEntry $je, User $by): void
    {
        if (! $je->isDraft()) {
            throw new \RuntimeException(
                "Only draft journal entries can be submitted. Current status: {$je->status}."
            );
        }

        $this->validateBalance($je);

        DB::transaction(function () use ($je, $by) {
            $old = $je->status;
            $je->forceFill([
                'status'      => 'pending_approval',
                'prepared_by' => $by->id,
            ])->save();

            FinanceAuditLog::record('approve', $je,
                ['status' => $old],
                ['status' => 'pending_approval', 'submitted_by' => $by->id]
            );
        });
    }

    /**
     * Approve a pending journal entry.
     * Moves status: pending_approval → approved
     */
    public function approve(JournalEntry $je, User $by, string $comments = ''): void
    {
        if ($je->status !== 'pending_approval') {
            throw new \RuntimeException(
                "Only pending-approval journal entries can be approved. Current status: {$je->status}."
            );
        }

        DB::transaction(function () use ($je, $by, $comments) {
            $old = $je->status;
            $je->forceFill([
                'status'      => 'approved',
                'approved_by' => $by->id,
            ])->save();

            FinanceAuditLog::record('approve', $je,
                ['status' => $old],
                ['status' => 'approved', 'approved_by' => $by->id, 'comments' => $comments]
            );
        });
    }

    /**
     * Return a pending journal entry to draft for revision.
     */
    public function returnForRevision(JournalEntry $je, User $by, string $comments = ''): void
    {
        if ($je->status !== 'pending_approval') {
            throw new \RuntimeException(
                'Only pending-approval journal entries can be returned for revision.'
            );
        }

        DB::transaction(function () use ($je, $by, $comments) {
            $old = $je->status;
            $je->forceFill(['status' => 'draft'])->save();

            FinanceAuditLog::record('reject', $je,
                ['status' => $old],
                ['status' => 'draft', 'returned_by' => $by->id, 'reason' => $comments]
            );
        });
    }

    /**
     * Reject a journal entry outright.
     */
    public function reject(JournalEntry $je, User $by, string $reason = ''): void
    {
        if (! in_array($je->status, ['pending_approval', 'approved'])) {
            throw new \RuntimeException('Only pending or approved entries can be rejected.');
        }

        DB::transaction(function () use ($je, $by, $reason) {
            $old = $je->status;
            $je->forceFill(['status' => 'rejected'])->save();

            FinanceAuditLog::record('reject', $je,
                ['status' => $old],
                ['status' => 'rejected', 'rejected_by' => $by->id, 'reason' => $reason]
            );
        });
    }

    // ─────────────────────────────────────────────────────────────────
    // Posting to General Ledger
    // ─────────────────────────────────────────────────────────────────

    /**
     * Post an approved journal entry to the General Ledger.
     *
     * Pre-conditions (all validated here):
     *   • Status must be 'approved' (or 'draft' for non-manual system sources)
     *   • Lines must balance (Σdebit == Σcredit)
     *   • Accounting period must be open
     *
     * Post-conditions:
     *   • All lines are written to finance_general_ledgers with running_balance
     *   • JE status → 'posted', posted_at → now()
     *   • Audit log entry recorded
     */
    public function post(JournalEntry $je, User $by): void
    {
        if (! in_array($je->status, ['approved', 'draft'])) {
            throw new \RuntimeException(
                "Only approved journal entries can be posted. Current status: {$je->status}."
            );
        }

        // System-source JEs (payroll, bank, etc.) may post from draft
        if ($je->status === 'draft' && $je->source === 'manual') {
            throw new \RuntimeException(
                'Manual journal entries must be approved before posting.'
            );
        }

        $this->validateBalance($je);
        $this->validatePeriod($je);

        DB::transaction(function () use ($je, $by) {
            $old = $je->status;

            // Post all lines to the General Ledger
            $this->glService->postJournalEntry($je);

            $je->forceFill([
                'status'      => 'posted',
                'posted_at'   => now(),
                'approved_by' => $by->id,
            ])->save();

            FinanceAuditLog::record('post', $je,
                ['status' => $old],
                ['status' => 'posted', 'posted_by' => $by->id, 'posted_at' => now()->toIso8601String()]
            );
        });
    }

    // ─────────────────────────────────────────────────────────────────
    // Reversal
    // ─────────────────────────────────────────────────────────────────

    /**
     * Reverse a posted journal entry.
     *
     * Creates a new "mirror" journal entry with all debits/credits swapped,
     * immediately posts it to the GL, and marks the original as 'reversed'.
     *
     * The reversal is dated today and posted to the current open period.
     * If no open period exists, the original period is reused (with a warning).
     *
     * @return JournalEntry  The newly created reversal JE
     * @throws \RuntimeException  If the original JE is not in 'posted' status
     */
    public function reverse(JournalEntry $je, User $by, string $reason = ''): JournalEntry
    {
        if (! $je->isPosted()) {
            throw new \RuntimeException(
                "Only posted journal entries can be reversed. Current status: {$je->status}."
            );
        }

        $je->loadMissing('lines');

        $reversalRef    = $this->generateReference(now()->year);
        $currentPeriod  = AccountingPeriod::current();
        $periodId       = $currentPeriod?->id ?? $je->accounting_period_id;

        $reversal = null;

        DB::transaction(function () use ($je, $by, $reason, $reversalRef, $periodId, &$reversal) {

            // 1. Create the reversal JE header
            $reversal = JournalEntry::create([
                'reference_number'      => $reversalRef,
                'accounting_period_id'  => $periodId,
                'transaction_date'      => now()->toDateString(),
                'description'           => "REVERSAL of [{$je->reference_number}]: {$reason}",
                'status'                => 'approved',   // will be posted immediately below
                'source'                => $je->source,
                'source_type'           => $je->source_type,
                'source_id'             => $je->source_id,
                'prepared_by'           => $by->id,
                'approved_by'           => $by->id,
                'currency_id'           => $je->currency_id,
                'exchange_rate_to_base' => $je->exchange_rate_to_base,
                'reversal_of_id'        => $je->id,
                'notes'                 => $reason,
            ]);

            // 2. Mirror all lines (swap debit ↔ credit)
            foreach ($je->lines as $line) {
                JournalEntryLine::create([
                    'journal_entry_id' => $reversal->id,
                    'account_id'       => $line->account_id,
                    'debit'            => $line->credit,   // ← swapped
                    'credit'           => $line->debit,    // ← swapped
                    'cost_center_id'   => $line->cost_center_id,
                    'donor_id'         => $line->donor_id,
                    'project_id'       => $line->project_id,
                    'activity_code'    => $line->activity_code,
                    'narration'        => "REVERSAL: {$line->narration}",
                ]);
            }

            // 3. Reload lines, then post reversal to GL
            $reversal->load('lines');
            $this->glService->postJournalEntry($reversal);

            $reversal->forceFill([
                'status'    => 'posted',
                'posted_at' => now(),
            ])->save();

            // 4. Mark the original JE as reversed
            $je->forceFill(['status' => 'reversed'])->save();

            // 5. Audit both records
            FinanceAuditLog::record('reverse', $je,
                ['status' => 'posted'],
                ['status' => 'reversed', 'reversal_ref' => $reversalRef, 'reversed_by' => $by->id]
            );

            FinanceAuditLog::record('post', $reversal,
                ['status' => 'approved'],
                ['status' => 'posted', 'reversal_of' => $je->reference_number]
            );
        });

        return $reversal;
    }

    // ─────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────

    /**
     * Compute a human-friendly balance summary for a set of line state arrays.
     * Used by the Filament form's live balance placeholder.
     *
     * @param  array  $lines  Raw array from $get('lines') in form state
     * @return array{total_debit: float, total_credit: float, difference: float, is_balanced: bool}
     */
    public static function computeBalanceSummary(array $lines): array
    {
        $totalDebit  = collect($lines)->sum(fn ($l) => (float) ($l['debit']  ?? 0));
        $totalCredit = collect($lines)->sum(fn ($l) => (float) ($l['credit'] ?? 0));
        $difference  = abs($totalDebit - $totalCredit);

        return [
            'total_debit'  => $totalDebit,
            'total_credit' => $totalCredit,
            'difference'   => $difference,
            'is_balanced'  => $difference < 0.005,
        ];
    }
}

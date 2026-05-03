<?php

namespace App\Models\Finance;

use App\Models\User;
use App\Traits\Finance\HasFinanceAuditLog;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankReconciliation extends Model
{
    use SoftDeletes, HasFinanceAuditLog;

    protected $table = 'finance_bank_reconciliations';

    protected $fillable = [
        'reference',
        'bank_account_id',
        'accounting_period_id',
        'statement_date',
        'statement_balance',
        'gl_balance',
        'outstanding_deposits',
        'outstanding_cheques',
        'adjusted_bank_balance',
        'difference',
        'status',
        'prepared_by',
        'reviewed_by',
        'reconciled_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'statement_date'        => 'date',
            'statement_balance'     => 'decimal:2',
            'gl_balance'            => 'decimal:2',
            'outstanding_deposits'  => 'decimal:2',
            'outstanding_cheques'   => 'decimal:2',
            'adjusted_bank_balance' => 'decimal:2',
            'difference'            => 'decimal:2',
            'reconciled_at'         => 'datetime',
        ];
    }

    // ── Helpers ────────────────────────────────────────────────────────

    public static function statuses(): array
    {
        return [
            'in_progress' => 'In Progress',
            'reconciled'  => 'Reconciled',
            'locked'      => 'Locked',
        ];
    }

    public function isReconciled(): bool
    {
        return abs((float) $this->difference) < 0.01;
    }

    // ── GL balance helper ──────────────────────────────────────────────

    /**
     * Calculate the true GL (book) balance for a bank account's linked
     * chart-of-account as of a given statement date.
     *
     * Bank accounts are asset accounts whose normal balance is DEBIT.
     * The GeneralLedger table stores a running_balance after every posting.
     * We take the most recent running_balance on or before $statementDate.
     */
    public static function glBalanceFor(int $bankAccountId, DateTimeInterface|string $statementDate): float
    {
        $bankAccount = BankAccount::with('chartOfAccount')->find($bankAccountId);

        if (! $bankAccount || ! $bankAccount->chartOfAccount) {
            // Fallback: no linked CoA — try opening balance
            return (float) ($bankAccount?->opening_balance ?? 0);
        }

        return $bankAccount->chartOfAccount->balanceAsOf($statementDate);
    }

    /**
     * Recompute outstanding_deposits, outstanding_cheques,
     * adjusted_bank_balance and difference from the items relation,
     * then persist the updated totals.
     *
     * Standard bank reconciliation formula:
     *   Adjusted Bank Balance = Statement Balance
     *                         + Deposits in Transit      (cleared by bank, not yet in books? NO)
     *                         + Outstanding Deposits     (in books, not yet cleared by bank)
     *                         - Outstanding Cheques/Payments (issued, not yet cleared by bank)
     *
     *   Difference = Adjusted Bank Balance − GL (Book) Balance
     *   Target     = 0.00
     */
    public function calculateTotals(): void
    {
        $items = $this->items()->get();

        // Items in transit that INCREASE the bank statement once cleared
        // (deposits recorded in books but not yet shown on bank statement)
        $outstandingDeposits = $items
            ->where('item_type', 'deposit')
            ->where('is_cleared', false)
            ->sum('amount');

        // Items that DECREASE the bank statement once cleared
        // (cheques/payments issued in books but not yet presented to bank)
        $outstandingCheques = $items
            ->whereIn('item_type', ['payment', 'bank_charge', 'interest', 'other'])
            ->where('is_cleared', false)
            ->sum('amount');

        // Adjusted bank balance reconciles the bank statement to book value:
        //   Start with what the BANK shows, add what bank hasn't received yet,
        //   subtract what bank hasn't paid out yet.
        $adjustedBankBalance = (float) $this->statement_balance
            + (float) $outstandingDeposits
            - (float) $outstandingCheques;

        // GL balance is the SOURCE OF TRUTH from the ledger — never overwrite it here.
        // It was set (from the ledger) when the reconciliation was first created.
        $difference = $adjustedBankBalance - (float) $this->gl_balance;

        $this->forceFill([
            'outstanding_deposits'  => $outstandingDeposits,
            'outstanding_cheques'   => $outstandingCheques,
            'adjusted_bank_balance' => $adjustedBankBalance,
            'difference'            => $difference,
            'status'                => abs($difference) < 0.01 ? 'reconciled' : 'in_progress',
        ])->save();
    }


    // ── Relationships ─────────────────────────────────────────────────

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(AccountingPeriod::class, 'accounting_period_id');
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BankReconciliationItem::class, 'reconciliation_id');
    }
}

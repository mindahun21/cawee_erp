<?php

namespace App\Models\Finance;

use App\Models\User;
use App\Traits\Finance\HasFinanceAuditLog;
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

    /**
     * Recompute outstanding_deposits, outstanding_cheques,
     * adjusted_bank_balance and difference from the items relation,
     * then persist the updated totals.
     */
    public function calculateTotals(): void
    {
        $items = $this->items()->get();

        $deposits = $items
            ->where('item_type', 'deposit')
            ->where('is_cleared', false)
            ->sum('amount');

        $cheques = $items
            ->whereIn('item_type', ['payment', 'bank_charge', 'interest', 'other'])
            ->where('is_cleared', false)
            ->sum('amount');

        $adjustedBankBalance = (float) $this->statement_balance
            + (float) $deposits
            - (float) $cheques;

        $difference = $adjustedBankBalance - (float) $this->gl_balance;

        $this->forceFill([
            'outstanding_deposits'  => $deposits,
            'outstanding_cheques'   => $cheques,
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

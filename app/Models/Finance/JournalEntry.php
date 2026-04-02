<?php

namespace App\Models\Finance;

use App\Models\User;
use App\Traits\Finance\HasFinanceAuditLog;
use App\Traits\Finance\HasApprovalWorkflow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class JournalEntry extends Model
{
    use SoftDeletes, HasFinanceAuditLog, HasApprovalWorkflow;

    protected $table = 'finance_journal_entries';

    protected $fillable = [
        'reference_number',
        'accounting_period_id',
        'transaction_date',
        'description',
        'status',
        'source',
        'source_type',
        'source_id',
        'prepared_by',
        'approved_by',
        'posted_at',
        'currency_id',
        'exchange_rate_to_base',
        'reversal_of_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date'      => 'date',
            'posted_at'             => 'datetime',
            'exchange_rate_to_base' => 'decimal:6',
        ];
    }

    // ── Status / Source helpers ───────────────────────────────────────

    public static function statuses(): array
    {
        return [
            'draft'            => 'Draft',
            'pending_approval' => 'Pending Approval',
            'approved'         => 'Approved',
            'posted'           => 'Posted',
            'reversed'         => 'Reversed',
        ];
    }

    public static function sources(): array
    {
        return [
            'manual'          => 'Manual',
            'payroll'         => 'Payroll',
            'bank'            => 'Bank',
            'petty_cash'      => 'Petty Cash',
            'procurement'     => 'Procurement',
            'perdiem'         => 'Per Diem',
            'opening_balance' => 'Opening Balance',
        ];
    }

    // ── Status predicates ─────────────────────────────────────────────

    public function isDraft(): bool            { return $this->status === 'draft'; }
    public function isPendingApproval(): bool  { return $this->status === 'pending_approval'; }
    public function isApproved(): bool         { return $this->status === 'approved'; }
    public function isPosted(): bool           { return $this->status === 'posted'; }
    public function isReversed(): bool         { return $this->status === 'reversed'; }

    public function isEditable(): bool
    {
        return $this->isDraft();
    }

    public function canBeSubmitted(): bool
    {
        return $this->isDraft() && $this->lines()->exists();
    }

    public function canBePosted(): bool
    {
        return $this->isApproved() || ($this->isDraft() && $this->source !== 'manual');
    }

    public function canBeReversed(): bool
    {
        return $this->isPosted();
    }

    // ── Balance helpers ───────────────────────────────────────────────

    /**
     * Computes |sum(debit) - sum(credit)| from in-memory lines collection.
     * Returns 0.00 when the entry is perfectly balanced.
     */
    public function getBalanceDifferenceAttribute(): float
    {
        $lines  = $this->relationLoaded('lines') ? $this->lines : $this->lines()->get();
        $debit  = $lines->sum(fn ($l) => (float) $l->debit);
        $credit = $lines->sum(fn ($l) => (float) $l->credit);

        return abs($debit - $credit);
    }

    public function getTotalDebitAttribute(): float
    {
        $lines = $this->relationLoaded('lines') ? $this->lines : $this->lines()->get();
        return (float) $lines->sum(fn ($l) => (float) $l->debit);
    }

    public function getTotalCreditAttribute(): float
    {
        $lines = $this->relationLoaded('lines') ? $this->lines : $this->lines()->get();
        return (float) $lines->sum(fn ($l) => (float) $l->credit);
    }

    public function isBalanced(): bool
    {
        return $this->balance_difference < 0.001;
    }

    // ── HasApprovalWorkflow contract ──────────────────────────────────

    public static function approvalStatusField(): string
    {
        return 'status';
    }

    // ── Relationships ─────────────────────────────────────────────────

    public function period(): BelongsTo
    {
        return $this->belongsTo(AccountingPeriod::class, 'accounting_period_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Currency::class);
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class, 'journal_entry_id');
    }

    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_of_id');
    }

    public function reversals(): HasMany
    {
        return $this->hasMany(self::class, 'reversal_of_id');
    }


}

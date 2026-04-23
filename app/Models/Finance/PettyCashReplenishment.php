<?php

namespace App\Models\Finance;

use App\Models\User;
use App\Traits\Finance\HasFinanceAuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PettyCashReplenishment extends Model
{
    use SoftDeletes, HasFinanceAuditLog;

    protected $table = 'finance_petty_cash_replenishments';

    protected $fillable = [
        'replenishment_number',
        'petty_cash_fund_id',
        'accounting_period_id',
        'request_date',
        'amount_requested',
        'amount_approved',
        'balance_before',
        'justification',
        'journal_entry_id',
        'bank_account_id',
        'status',
        'requested_by',
        'approved_by',
        'approved_at',
        'disbursed_by',
        'disbursed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'request_date'     => 'date',
            'amount_requested' => 'decimal:2',
            'amount_approved'  => 'decimal:2',
            'balance_before'   => 'decimal:2',
            'approved_at'      => 'datetime',
            'disbursed_at'     => 'datetime',
        ];
    }

    // ── Static helpers ─────────────────────────────────────────────────

    public static function statuses(): array
    {
        return [
            'draft'    => 'Draft',
            'pending'  => 'Pending Approval',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'disbursed'=> 'Disbursed & Posted',
        ];
    }

    // ── Status predicates ─────────────────────────────────────────────

    public function isDraft(): bool     { return $this->status === 'draft'; }
    public function isPending(): bool   { return $this->status === 'pending'; }
    public function isApproved(): bool  { return $this->status === 'approved'; }
    public function isRejected(): bool  { return $this->status === 'rejected'; }
    public function isDisbursed(): bool { return $this->status === 'disbursed'; }

    // ── Relationships ─────────────────────────────────────────────────

    public function fund(): BelongsTo
    {
        return $this->belongsTo(PettyCashFund::class, 'petty_cash_fund_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(AccountingPeriod::class, 'accounting_period_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function disbursedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disbursed_by');
    }
}

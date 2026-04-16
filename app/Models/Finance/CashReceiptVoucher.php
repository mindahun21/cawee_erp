<?php

namespace App\Models\Finance;

use App\Models\Currency;
use App\Models\Donor;
use App\Models\Project;
use App\Models\User;
use App\Traits\Finance\HasFinanceAuditLog;
use App\Traits\Finance\HasApprovalWorkflow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashReceiptVoucher extends Model
{
    use SoftDeletes, HasFinanceAuditLog, HasApprovalWorkflow;

    protected $table = 'finance_cash_receipt_vouchers';

    protected $fillable = [
        'crv_number',
        'accounting_period_id',
        'receipt_date',
        'received_from',
        'donor_id',
        'bank_account_id',
        'income_type',
        'amount',
        'currency_id',
        'exchange_rate_to_base',
        'amount_in_base',
        'project_id',
        'cost_center_id',
        'activity_code',
        'donor_code',
        'journal_entry_id',
        'status',
        'prepared_by',
        'approved_by',
        'approved_at',
        'posted_at',
        'document_attachments',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'receipt_date'          => 'date',
            'amount'                => 'decimal:2',
            'exchange_rate_to_base' => 'decimal:6',
            'amount_in_base'        => 'decimal:2',
            'approved_at'           => 'datetime',
            'posted_at'             => 'datetime',
            'document_attachments'  => 'array',
        ];
    }

    // ── Static helpers ─────────────────────────────────────────────────

    public static function statuses(): array
    {
        return [
            'draft'            => 'Draft',
            'pending_approval' => 'Pending Approval',
            'approved'         => 'Approved',
            'posted'           => 'Posted',
            'rejected'         => 'Rejected',
        ];
    }

    public static function incomeTypes(): array
    {
        return [
            'grant'    => 'Grant',
            'donation' => 'Donation',
            'service'  => 'Service Fee',
            'interest' => 'Bank Interest',
            'other'    => 'Other Income',
        ];
    }

    // ── Status predicates ─────────────────────────────────────────────

    public function isDraft(): bool           { return $this->status === 'draft'; }
    public function isPendingApproval(): bool { return $this->status === 'pending_approval'; }
    public function isApproved(): bool        { return $this->status === 'approved'; }
    public function isPosted(): bool          { return $this->status === 'posted'; }
    public function isRejected(): bool        { return $this->status === 'rejected'; }

    public static function approvalStatusField(): string { return 'status'; }

    // ── Relationships ─────────────────────────────────────────────────

    public function period(): BelongsTo
    {
        return $this->belongsTo(AccountingPeriod::class, 'accounting_period_id');
    }

    public function donor(): BelongsTo
    {
        return $this->belongsTo(Donor::class, 'donor_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class, 'cost_center_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}

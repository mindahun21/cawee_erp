<?php

namespace App\Models\Finance;

use App\Models\User;
use App\Traits\Finance\HasFinanceAuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Donor;
use App\Models\Project;

class PaymentRequisition extends Model
{
    use SoftDeletes, HasFinanceAuditLog;

    protected $table = 'finance_payment_requisitions';

    protected $fillable = [
        'pr_number', 'requisition_date',
        'procurement_po_id', 'supplier_id',
        'payee_name', 'payee_bank_name', 'payee_account_number', 'payee_tin',
        'invoice_number', 'invoice_date', 'invoice_attachment',
        'total_amount', 'currency_id', 'exchange_rate_to_base',
        'withholding_tax_amount', 'vat_amount', 'net_payable',
        'cost_center_id', 'project_id', 'donor_id', 'activity_code', 'donor_code',
        'document_attachments', 'status', 'approval_stage',
        'prepared_by', 'approved_by', 'approved_at',
        'payment_voucher_id', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'requisition_date'        => 'date',
            'invoice_date'            => 'date',
            'total_amount'            => 'decimal:2',
            'withholding_tax_amount'  => 'decimal:2',
            'vat_amount'              => 'decimal:2',
            'net_payable'             => 'decimal:2',
            'document_attachments'    => 'array',
            'approved_at'             => 'datetime',
        ];
    }

    public static function statuses(): array
    {
        return [
            'draft'            => 'Draft',
            'pending_approval' => 'Pending Approval',
            'approved'         => 'Approved',
            'rejected'         => 'Rejected',
            'paid'             => 'Paid',
        ];
    }

    public function isDraft(): bool          { return $this->status === 'draft'; }
    public function isPendingApproval(): bool { return $this->status === 'pending_approval'; }
    public function isApproved(): bool        { return $this->status === 'approved'; }
    public function isRejected(): bool        { return $this->status === 'rejected'; }
    public function isPaid(): bool            { return $this->status === 'paid'; }
    public function isEditable(): bool        { return $this->isDraft(); }

    // ── Relationships ──────────────────────────────────────────────────

    public function lines(): HasMany
    {
        return $this->hasMany(PaymentRequisitionLine::class, 'payment_requisition_id');
    }

    public function approvalHistories(): MorphMany
    {
        return $this->morphMany(ApprovalHistory::class, 'approvable');
    }

    public function currency(): BelongsTo   { return $this->belongsTo(\App\Models\Currency::class, 'currency_id'); }
    public function costCenter(): BelongsTo { return $this->belongsTo(CostCenter::class, 'cost_center_id'); }
    public function project(): BelongsTo    { return $this->belongsTo(Project::class, 'project_id'); }
    public function donor(): BelongsTo      { return $this->belongsTo(Donor::class, 'donor_id'); }
    public function preparedBy(): BelongsTo { return $this->belongsTo(User::class, 'prepared_by'); }
    public function approvedBy(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
}

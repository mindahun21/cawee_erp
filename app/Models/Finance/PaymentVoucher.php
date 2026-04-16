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

class PaymentVoucher extends Model
{
    use SoftDeletes, HasFinanceAuditLog, HasApprovalWorkflow;

    protected $table = 'finance_payment_vouchers';

    protected $fillable = [
        'pv_number',
        'accounting_period_id',
        'payment_date',
        'payee_name',
        'payee_type',
        'payee_id',
        'payee_tin',
        'bank_account_id',
        'payment_method',
        'cheque_number',
        'transfer_reference',
        'gross_amount',
        'currency_id',
        'exchange_rate_to_base',
        'withholding_tax_rate',
        'withholding_tax_amount',
        'vat_type',
        'vat_rate',
        'vat_amount',
        'net_amount',
        'project_id',
        'cost_center_id',
        'donor_id',
        'activity_code',
        'donor_code',
        'payment_requisition_id',
        'invoice_number',
        'invoice_date',
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
            'payment_date'          => 'date',
            'invoice_date'          => 'date',
            'gross_amount'          => 'decimal:2',
            'exchange_rate_to_base' => 'decimal:6',
            'withholding_tax_rate'  => 'decimal:4',
            'withholding_tax_amount'=> 'decimal:2',
            'vat_rate'              => 'decimal:4',
            'vat_amount'            => 'decimal:2',
            'net_amount'            => 'decimal:2',
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

    public static function payeeTypes(): array
    {
        return [
            'supplier' => 'Supplier / Vendor',
            'employee' => 'Employee',
            'other'    => 'Other',
        ];
    }

    public static function paymentMethods(): array
    {
        return [
            'cash'          => 'Cash',
            'cheque'        => 'Cheque',
            'bank_transfer' => 'Bank Transfer',
            'mobile_money'  => 'Mobile Money',
        ];
    }

    public static function vatTypes(): array
    {
        return [
            'collected' => 'VAT Collected (Output)',
            'payable'   => 'VAT Payable (Input)',
            'exempt'    => 'VAT Exempt',
            'none'      => 'No VAT',
        ];
    }

    // ── Status predicates ─────────────────────────────────────────────

    public function isDraft(): bool           { return $this->status === 'draft'; }
    public function isPendingApproval(): bool { return $this->status === 'pending_approval'; }
    public function isApproved(): bool        { return $this->status === 'approved'; }
    public function isPosted(): bool          { return $this->status === 'posted'; }
    public function isRejected(): bool        { return $this->status === 'rejected'; }

    public static function approvalStatusField(): string { return 'status'; }

    /**
     * Compute net amount from gross minus withholding tax.
     */
    public function computeNetAmount(): float
    {
        return (float) $this->gross_amount - (float) $this->withholding_tax_amount;
    }

    // ── Relationships ─────────────────────────────────────────────────

    public function period(): BelongsTo
    {
        return $this->belongsTo(AccountingPeriod::class, 'accounting_period_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Currency::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class, 'cost_center_id');
    }

    public function donor(): BelongsTo
    {
        return $this->belongsTo(Donor::class, 'donor_id');
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

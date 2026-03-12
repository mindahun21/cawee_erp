<?php

namespace App\Models\Procurement;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Invoice extends Model
{
    use SoftDeletes;

    protected $table = 'procurement_invoices';

    // ── Status constants ─────────────────────────────────────────────
    const STATUS_DRAFT     = 'Draft';
    const STATUS_SUBMITTED = 'Submitted';
    const STATUS_MATCHED   = 'Matched';
    const STATUS_APPROVED  = 'Approved';
    const STATUS_PAID      = 'Paid';
    const STATUS_OVERDUE   = 'Overdue';
    const STATUS_DISPUTED  = 'Disputed';
    const STATUS_REJECTED  = 'Rejected';

    protected $fillable = [
        'invoice_number', 'supplier_invoice_number', 'purchase_order_id', 'supplier_id',
        'created_by', 'invoice_date', 'due_date', 'subtotal', 'tax_amount',
        'total_amount', 'currency', 'status', 'notes', 'attachments',
        // Finance approval
        'finance_status', 'finance_approved_by', 'finance_approved_at', 'finance_remarks',
        // Director approval
        'director_status', 'director_approved_by', 'director_approved_at',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date'       => 'date',
            'due_date'           => 'date',
            'subtotal'           => 'decimal:2',
            'tax_amount'         => 'decimal:2',
            'total_amount'       => 'decimal:2',
            'finance_approved_at' => 'datetime',
            'director_approved_at' => 'datetime',
            'attachments'        => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $inv) {
            if (empty($inv->invoice_number)) {
                $year = now()->format('Y');
                $seq  = static::withTrashed()->whereYear('created_at', $year)->count() + 1;
                $inv->invoice_number = sprintf('INV-%s-%04d', $year, $seq);
            }
            if (empty($inv->created_by)) {
                $inv->created_by = auth()->id();
            }
        });
    }

    // ── Relationships ───────────────────────────────────────────────
    public function purchaseOrder(): BelongsTo   { return $this->belongsTo(PurchaseOrder::class); }
    public function supplier(): BelongsTo        { return $this->belongsTo(Supplier::class); }
    public function creator(): BelongsTo         { return $this->belongsTo(User::class, 'created_by'); }
    public function financeApprover(): BelongsTo { return $this->belongsTo(User::class, 'finance_approved_by'); }
    public function directorApprover(): BelongsTo { return $this->belongsTo(User::class, 'director_approved_by'); }
    public function threeWayMatch(): HasOne       { return $this->hasOne(ThreeWayMatch::class); }
    public function payment(): HasOne             { return $this->hasOne(Payment::class); }

    // ── Stage predicates ─────────────────────────────────────────────
    public function canFinanceApprove(): bool
    {
        return $this->status === self::STATUS_MATCHED
            && $this->finance_status === 'Pending';
    }

    public function canDirectorApprove(): bool
    {
        return $this->finance_status === 'Approved'
            && $this->director_status === 'Pending';
    }

    public function isFullyApproved(): bool
    {
        return $this->director_status === 'Approved';
    }

    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast()
            && !in_array($this->status, [self::STATUS_PAID, self::STATUS_REJECTED]);
    }

    public function getCurrentStageAttribute(): string
    {
        if ($this->status === self::STATUS_PAID) return 'Paid ✓';
        if ($this->status === self::STATUS_REJECTED) return 'Rejected';
        if ($this->finance_status === 'Pending') return 'Awaiting Finance';
        if ($this->director_status === 'Pending') return 'Awaiting Director';
        if ($this->director_status === 'Approved') return 'Approved — Awaiting Payment';
        return $this->status;
    }
}

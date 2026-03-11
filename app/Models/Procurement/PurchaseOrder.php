<?php

namespace App\Models\Procurement;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    use SoftDeletes;

    protected $table = 'procurement_purchase_orders';

    // ── Status constants ─────────────────────────────────────────────
    const STATUS_DRAFT        = 'Draft';
    const STATUS_PENDING      = 'Pending Approval';
    const STATUS_APPROVED     = 'Approved';
    const STATUS_SENT         = 'Sent to Supplier';
    const STATUS_ACKNOWLEDGED = 'Acknowledged';
    const STATUS_PARTIAL      = 'Partially Received';
    const STATUS_RECEIVED     = 'Received';
    const STATUS_CLOSED       = 'Closed';
    const STATUS_CANCELLED    = 'Cancelled';

    protected $fillable = [
        'po_number', 'version', 'requisition_id', 'tender_id', 'bid_id',
        'supplier_id', 'created_by', 'order_date', 'delivery_date',
        'supplier_acknowledged_at', 'delivery_location', 'payment_terms',
        'currency', 'subtotal', 'tax_rate', 'tax_amount',
        'discount_amount', 'other_charges', 'other_charges_description',
        'total_amount', 'notes', 'overall_status',
        // Approval stages
        'procurement_officer_status', 'procurement_officer_approved_by', 'procurement_officer_approved_at',
        'finance_status', 'finance_approved_by', 'finance_approved_at',
        'director_status', 'director_approved_by', 'director_approved_at',
        'attachments',
    ];

    protected function casts(): array
    {
        return [
            'order_date'                       => 'date',
            'delivery_date'                    => 'date',
            'supplier_acknowledged_at'         => 'date',
            'subtotal'                         => 'decimal:2',
            'tax_rate'                         => 'decimal:2',
            'tax_amount'                       => 'decimal:2',
            'discount_amount'                  => 'decimal:2',
            'other_charges'                    => 'decimal:2',
            'total_amount'                     => 'decimal:2',
            'procurement_officer_approved_at'  => 'datetime',
            'finance_approved_at'              => 'datetime',
            'director_approved_at'             => 'datetime',
            'attachments'                      => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $po) {
            if (empty($po->po_number)) {
                $year = now()->format('Y');
                $seq  = static::withTrashed()->whereYear('created_at', $year)->count() + 1;
                $po->po_number = sprintf('PO-%s-%04d', $year, $seq);
            }
            if (empty($po->created_by)) {
                $po->created_by = auth()->id();
            }
        });

        static::saved(function (self $po) {
            // Recompute header totals from persisted line items.
            $items = $po->items()->get();
            $untaxedSubtotal = 0;
            $totalTax = 0;

            foreach ($items as $item) {
                // Line base without tax
                $base = (float)$item->quantity * (float)$item->unit_price;
                $disc = $base * ((float)$item->discount_percent / 100);
                $taxableBase = max(0, $base - $disc);
                $taxAmount = $taxableBase * ((float)$item->tax_rate / 100);

                $untaxedSubtotal += $taxableBase;
                $totalTax += $taxAmount;
            }

            $headerDiscount = (float) $po->discount_amount;
            $otherCharges   = (float) $po->other_charges;

            $realSubtotal = max(0, $untaxedSubtotal - $headerDiscount);

            $po->withoutEvents(function () use ($po, $untaxedSubtotal, $totalTax, $realSubtotal, $otherCharges) {
                $po->subtotal     = round($untaxedSubtotal, 2);
                $po->tax_amount   = round($totalTax, 2);
                $po->total_amount = round($realSubtotal + $totalTax + $otherCharges, 2);
                $po->saveQuietly();
            });
        });
    }

    // ── Relationships ─────────────────────────────────────────────────
    public function requisition(): BelongsTo   { return $this->belongsTo(Requisition::class); }
    public function tender(): BelongsTo        { return $this->belongsTo(Tender::class); }
    public function bid(): BelongsTo           { return $this->belongsTo(Bid::class); }
    public function supplier(): BelongsTo      { return $this->belongsTo(Supplier::class); }
    public function creator(): BelongsTo       { return $this->belongsTo(User::class, 'created_by'); }
    public function procurementOfficerApprover(): BelongsTo { return $this->belongsTo(User::class, 'procurement_officer_approved_by'); }
    public function financeApprover(): BelongsTo  { return $this->belongsTo(User::class, 'finance_approved_by'); }
    public function directorApprover(): BelongsTo { return $this->belongsTo(User::class, 'director_approved_by'); }
    public function items(): HasMany           { return $this->hasMany(PurchaseOrderItem::class); }
    public function goodsReceipts(): HasMany   { return $this->hasMany(GoodsReceipt::class); }
    public function invoices(): HasMany        { return $this->hasMany(Invoice::class); }

    // ── Stage predicates ─────────────────────────────────────────────
    public function canProcurementOfficerApprove(): bool
    {
        return $this->overall_status === self::STATUS_PENDING
            && $this->procurement_officer_status === 'Pending';
    }

    public function canFinanceApprove(): bool
    {
        return $this->procurement_officer_status === 'Approved'
            && $this->finance_status === 'Pending';
    }

    public function canDirectorApprove(): bool
    {
        return $this->finance_status === 'Approved'
            && $this->director_status === 'Pending';
    }

    public function isRejected(): bool
    {
        return in_array('Rejected', [
            $this->procurement_officer_status, $this->finance_status, $this->director_status,
        ]);
    }

    public function isFullyApproved(): bool
    {
        return $this->overall_status === self::STATUS_APPROVED
            || $this->overall_status === self::STATUS_SENT
            || $this->overall_status === self::STATUS_ACKNOWLEDGED
            || $this->overall_status === self::STATUS_CLOSED;
    }

    public function getCurrentStageAttribute(): string
    {
        if ($this->isRejected()) return 'Rejected';
        if ($this->overall_status === self::STATUS_DRAFT) return 'Draft';
        if ($this->procurement_officer_status === 'Pending') return 'Awaiting Procurement Officer';
        if ($this->finance_status === 'Pending') return 'Awaiting Finance';
        if ($this->director_status === 'Pending') return 'Awaiting Director';
        return $this->overall_status;
    }
}

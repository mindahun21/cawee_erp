<?php

namespace App\Models\Procurement;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThreeWayMatch extends Model
{
    protected $table = 'procurement_three_way_matches';

    protected $fillable = [
        'invoice_id', 'purchase_order_id', 'goods_receipt_id',
        'match_status', 'po_amount', 'grn_amount', 'invoice_amount',
        'variance', 'exception_notes', 'matched_by', 'matched_at',
    ];

    protected function casts(): array
    {
        return [
            'po_amount'      => 'decimal:2',
            'grn_amount'     => 'decimal:2',
            'invoice_amount' => 'decimal:2',
            'variance'       => 'decimal:2',
            'matched_at'     => 'datetime',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────
    public function invoice(): BelongsTo      { return $this->belongsTo(Invoice::class); }
    public function purchaseOrder(): BelongsTo { return $this->belongsTo(PurchaseOrder::class); }
    public function goodsReceipt(): BelongsTo  { return $this->belongsTo(GoodsReceipt::class); }
    public function matcher(): BelongsTo       { return $this->belongsTo(User::class, 'matched_by'); }

    // ── Computed ─────────────────────────────────────────────────────
    public function isFullyMatched(): bool { return $this->match_status === 'Matched'; }

    public function getStatusColorAttribute(): string
    {
        return match ($this->match_status) {
            'Matched'           => 'success',
            'Price Mismatch'    => 'warning',
            'Quantity Mismatch' => 'warning',
            'PO Mismatch'       => 'danger',
            'Exception'         => 'danger',
            default             => 'gray',
        };
    }
}

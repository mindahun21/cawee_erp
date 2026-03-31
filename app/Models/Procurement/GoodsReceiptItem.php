<?php

namespace App\Models\Procurement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReceiptItem extends Model
{
    protected $table = 'procurement_goods_receipt_items';

    protected $fillable = [
        'goods_receipt_id', 'po_item_id', 'received_quantity',
        'accepted_quantity', 'rejected_quantity', 'condition', 'inspection_remarks',
    ];

    protected function casts(): array
    {
        return [
            'received_quantity' => 'decimal:4',
            'accepted_quantity' => 'decimal:4',
            'rejected_quantity' => 'decimal:4',
        ];
    }

    // ── Relationships ───────────────────────────────────────────────
    public function goodsReceipt(): BelongsTo { return $this->belongsTo(GoodsReceipt::class); }
    public function poItem(): BelongsTo       { return $this->belongsTo(PurchaseOrderItem::class, 'po_item_id'); }
}

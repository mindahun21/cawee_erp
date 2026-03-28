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
        'item_type', 'registered_at', 'registration_ref',
    ];

    protected function casts(): array
    {
        return [
            'received_quantity' => 'decimal:4',
            'accepted_quantity' => 'decimal:4',
            'rejected_quantity' => 'decimal:4',
            'registered_at'    => 'datetime',
        ];
    }

    /** Whether this item has been posted to Inventory or Asset register. */
    public function isRegistered(): bool
    {
        return $this->registered_at !== null;
    }

    /** Human-readable label for the item_type field. */
    public function itemTypeLabel(): string
    {
        return match ($this->item_type ?? 'consumable') {
            'fixed_asset' => 'Fixed Asset',
            'skip'        => 'Skip Registration',
            default       => 'Consumable (Inventory)',
        };
    }

    // ── Relationships ───────────────────────────────────────────────
    public function goodsReceipt(): BelongsTo { return $this->belongsTo(GoodsReceipt::class); }
    public function poItem(): BelongsTo       { return $this->belongsTo(PurchaseOrderItem::class, 'po_item_id'); }
}

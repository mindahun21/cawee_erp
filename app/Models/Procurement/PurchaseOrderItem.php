<?php

namespace App\Models\Procurement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    protected $table = 'procurement_purchase_order_items';

    protected $fillable = [
        'purchase_order_id', 'description', 'unit', 'quantity',
        'unit_price', 'total_price', 'received_quantity', 'specifications',
    ];

    protected function casts(): array
    {
        return [
            'quantity'          => 'decimal:4',
            'unit_price'        => 'decimal:2',
            'total_price'       => 'decimal:2',
            'received_quantity' => 'decimal:4',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $item) {
            $item->total_price = round(
                (float)$item->quantity * (float)$item->unit_price,
                2
            );
        });
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function getRemainingQuantityAttribute(): float
    {
        return max(0, (float)$this->quantity - (float)$this->received_quantity);
    }
}

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
        'tax_rate', 'tax_amount', 'discount_percent', 'discount_amount', 'line_total',
    ];

    protected function casts(): array
    {
        return [
            'quantity'          => 'decimal:4',
            'unit_price'        => 'decimal:2',
            'total_price'       => 'decimal:2',
            'received_quantity' => 'decimal:4',
            'tax_rate'          => 'decimal:2',
            'tax_amount'        => 'decimal:2',
            'discount_percent'  => 'decimal:2',
            'discount_amount'   => 'decimal:2',
            'line_total'        => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $item) {
            $baseTotal = (float)$item->quantity * (float)$item->unit_price;
            $item->total_price = round($baseTotal, 2);

            $discountAmount = round($baseTotal * ((float)$item->discount_percent / 100), 2);
            $item->discount_amount = $discountAmount;

            $taxableAmount = max(0, $baseTotal - $discountAmount);
            $taxAmount = round($taxableAmount * ((float)$item->tax_rate / 100), 2);
            $item->tax_amount = $taxAmount;

            $item->line_total = round($taxableAmount + $taxAmount, 2);
        });

        static::saved(function (self $item) {
            if ($item->purchaseOrder) {
                // Trigger the PO to re-save so it recomputes and saves its header-level totals
                $item->purchaseOrder->save();
            }
        });

        static::deleted(function (self $item) {
            if ($item->purchaseOrder) {
                $item->purchaseOrder->save();
            }
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

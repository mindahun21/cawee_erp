<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'description',
        'model',
        'unit',
        'quantity',
        'unit_cost',
        'total_cost',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    protected static function booted()
    {
        static::saving(function ($item) {
            $item->total_cost = $item->quantity * $item->unit_cost;
        });

        static::saved(function ($item) {
            $item->purchaseOrder?->recalculateTotal();
        });

        static::deleted(function ($item) {
            $item->purchaseOrder?->recalculateTotal();
        });
    }
}

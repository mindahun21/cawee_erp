<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseRequestItem extends Model
{
    protected $fillable = [
        'purchase_request_id',
        'item_id',
        'description',
        'model',
        'unit',
        'quantity',
        'unit_price',
        'specification',
        'subtotal',
        'tax_id',
        'tax_value',
        'total',
        'estimated_cost',
        'remark',
    ];

    protected $casts = [
        'estimated_cost' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax_value' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function tax()
    {
        return $this->belongsTo(Tax::class);
    }

    public function purchaseRequest()
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    protected static function booted()
    {
        static::saving(function ($item) {
            $item->subtotal = (float) $item->quantity * (float) $item->unit_price;
            $taxRate = $item->tax?->rate ?? 0;
            $item->tax_value = $item->subtotal * ($taxRate / 100);
            $item->total = $item->subtotal + $item->tax_value;
        });

        static::saved(function ($item) {
            $item->purchaseRequest?->recalculateTotal();
        });

        static::deleted(function ($item) {
            $item->purchaseRequest?->recalculateTotal();
        });
    }
}

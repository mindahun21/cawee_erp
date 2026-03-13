<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Procurement\Supplier;

class ItemWarehouse extends Model
{
    protected $table = 'item_warehouse';

    protected $fillable = [
        'item_id',
        'warehouse_id',
        'sku',
        'acquisition_type_id',
        'currency_id',
        'purchase_cost',
        'purchase_date',
        'warranty_expiry',
        'supplier_id',
        'donor_id',
        'quantity',
        'min_stock_value',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'warranty_expiry' => 'date',
        'purchase_cost' => 'decimal:2',
        'quantity' => 'integer',
        'min_stock_value' => 'integer',
    ];

    public static function generateUniqueSku(): string
    {
        return \App\Models\PrefixSetting::generateNextCode('inventory_sku');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($itemWarehouse) {
            if (!$itemWarehouse->sku) {
                $itemWarehouse->sku = self::generateUniqueSku();
            }
        });

        static::created(function ($itemWarehouse) {
            \App\Models\PrefixSetting::updateNextNumberFromCode('inventory_sku', $itemWarehouse->sku);
        });
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function acquisitionType()
    {
        return $this->belongsTo(AcquisitionType::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function donor()
    {
        return $this->belongsTo(Donor::class);
    }
}

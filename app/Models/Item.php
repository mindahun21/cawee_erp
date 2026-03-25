<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $fillable = [
        'name',
        'item_code',
        'item_category_id',
        'item_type',
        'item_type_id',
        'barcode',
        'description',
        'reorder_level',
        'asset_model_id',
        'unit_id',
        'note',
        'image',
    ];

    public function itemCategory()
    {
        return $this->belongsTo(ItemCategory::class);
    }

    public function itemTypeRecord()
    {
        return $this->belongsTo(ItemType::class, 'item_type_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function assetModel()
    {
        return $this->belongsTo(AssetModel::class);
    }

    public function warehouses()
    {
        return $this->belongsToMany(Warehouse::class)
            ->withPivot([
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
            ])
            ->withTimestamps();
    }
}

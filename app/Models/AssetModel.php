<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetModel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'asset_type_id',
        'asset_manufacturer_id',
        'asset_category_id',
        'model_number',
        'depreciation_id',
        'eol_months',
        'is_requestable',
        'note',
        'image',
    ];

    protected $casts = [
        'is_requestable' => 'boolean',
    ];

    public function manufacturer()
    {
        return $this->belongsTo(AssetManufacturer::class, 'asset_manufacturer_id');
    }

    public function type()
    {
        return $this->belongsTo(AssetType::class, 'asset_type_id');
    }

    public function category()
    {
        return $this->belongsTo(AssetCategory::class, 'asset_category_id');
    }

    public function depreciation()
    {
        return $this->belongsTo(Depreciation::class);
    }

    public function assets()
    {
        return $this->hasMany(Asset::class);
    }
}

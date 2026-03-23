<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Warehouse extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'warehouse_code',
        'name',
        'manager_id',
        'warehouse_type_id',
        'is_active',
        'order',
        'address',
        'city',
        'province',
        'postal_code',
        'country_id',
        'note',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function manager()
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    public function warehouseType()
    {
        return $this->belongsTo(WarehouseType::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($warehouse) {
            if (!$warehouse->warehouse_code) {
                $warehouse->warehouse_code = self::generateUniqueCode();
            }
        });

        static::created(function ($warehouse) {
            \App\Models\PrefixSetting::updateNextNumberFromCode('warehouse_code', $warehouse->warehouse_code);
        });
    }

    public static function generateUniqueCode(): string
    {
        return \App\Models\PrefixSetting::generateNextCode('warehouse_code');
    }

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'warehouse_employee');
    }

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(Item::class);
    }
}

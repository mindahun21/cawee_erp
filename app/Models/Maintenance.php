<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Maintenance extends Model
{
    protected $fillable = [
        'asset_id',
        'supplier_id',
        'maintenance_type_id',
        'title',
        'start_date',
        'completion_date',
        'is_warranty_improvement',
        'currency_id',
        'cost',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'completion_date' => 'date',
        'is_warranty_improvement' => 'boolean',
        'cost' => 'decimal:2',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function supplier()
    {
        return $this->belongsTo(\App\Models\Procurement\Supplier::class, 'supplier_id');
    }

    public function maintenanceType()
    {
        return $this->belongsTo(MaintenanceType::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }
}

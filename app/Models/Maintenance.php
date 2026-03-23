<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Maintenance extends Model
{
    protected $fillable = [
        'asset_id',
        'supplier_id',
        'performed_by_id',
        'maintenance_type_id',
        'title',
        'status',
        'status_id',
        'priority',
        'priority_id',
        'description',
        'start_date',
        'completion_date',
        'next_scheduled_date',
        'is_warranty_improvement',
        'currency_id',
        'cost',
        'downtime_hours',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'completion_date' => 'date',
        'next_scheduled_date' => 'date',
        'is_warranty_improvement' => 'boolean',
        'cost' => 'decimal:2',
        'downtime_hours' => 'decimal:2',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function supplier()
    {
        return $this->belongsTo(\App\Models\Procurement\Supplier::class, 'supplier_id');
    }

    public function performedBy()
    {
        return $this->belongsTo(Employee::class, 'performed_by_id');
    }

    public function maintenanceType()
    {
        return $this->belongsTo(MaintenanceType::class);
    }

    public function statusRecord()
    {
        return $this->belongsTo(MaintenanceStatus::class, 'status_id');
    }

    public function priorityRecord()
    {
        return $this->belongsTo(MaintenancePriority::class, 'priority_id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }
}

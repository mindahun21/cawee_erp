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

    protected static function booted(): void
    {
        static::saved(function (Maintenance $maintenance) {
            $status = strtolower($maintenance->statusRecord?->name ?? '');
            $asset = $maintenance->asset;
            if (!$asset) return;

            if (in_array($status, ['in progress', 'scheduled'])) {
                // Set asset to Maintenance status
                $maintStatus = \App\Models\AssetStatus::firstOrCreate(['name' => 'Maintenance']);
                $asset->update(['asset_status_id' => $maintStatus->id]);

                // If it's a mirrored vehicle, set it to inactive
                if (str_starts_with($asset->asset_tag ?? '', 'VEH-')) {
                    $vehicleId = (int) str_replace('VEH-', '', $asset->asset_tag);
                    \App\Models\Vehicle::where('id', $vehicleId)->update([
                        'is_active' => false,
                        'remarks' => "In Maintenance (Reference: {$maintenance->title})"
                    ]);
                }
            } elseif ($status === 'completed') {
                // Set asset to Available status
                $availStatus = \App\Models\AssetStatus::firstOrCreate(['name' => 'Available']);
                $asset->update(['asset_status_id' => $availStatus->id]);

                // If it's a mirrored vehicle, restore to active
                if (str_starts_with($asset->asset_tag ?? '', 'VEH-')) {
                    $vehicleId = (int) str_replace('VEH-', '', $asset->asset_tag);
                    \App\Models\Vehicle::where('id', $vehicleId)->update([
                        'is_active' => true,
                        'remarks' => "Maintenance Completed (Reference: {$maintenance->title})"
                    ]);
                }
            }
        });
    }
}

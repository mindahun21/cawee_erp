<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehicleMaintenance extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'vehicle_id',
        'service_date',
        'service_type',
        'service_type_id',
        'description',
        'cost',
        'next_service_date',
        'remarks'
    ];

    protected $casts = [
        'service_date' => 'date',
        'next_service_date' => 'date',
        'cost' => 'decimal:2',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function serviceTypeRecord()
    {
        return $this->belongsTo(VehicleServiceType::class, 'service_type_id');
    }

    protected static function booted(): void
    {
        static::saved(function (VehicleMaintenance $vm) {
            $asset = Asset::where('asset_tag', 'VEH-' . $vm->vehicle_id)->first();
            if (!$asset) return;

            // Map Vehicle Service Type to General Maintenance Type if possible
            $mType = \App\Models\MaintenanceType::firstOrCreate(['name' => 'Vehicle Service']);

            \App\Models\Maintenance::updateOrCreate(
                ['notes' => 'V-MAINT-' . $vm->id], // Identifier
                [
                    'asset_id' => $asset->id,
                    'maintenance_type_id' => $mType->id,
                    'title' => 'Vehicle Maintenance: ' . ($vm->serviceTypeRecord?->name ?: 'Service'),
                    'description' => $vm->description,
                    'start_date' => $vm->service_date,
                    'next_scheduled_date' => $vm->next_service_date,
                    'cost' => $vm->cost,
                    'notes' => 'V-MAINT-' . $vm->id . (filled($vm->remarks) ? "\n" . $vm->remarks : ''),
                ]
            );
        });

        static::deleted(function (VehicleMaintenance $vm) {
            \App\Models\Maintenance::where('notes', 'like', 'V-MAINT-' . $vm->id . '%')->delete();
        });
    }
}

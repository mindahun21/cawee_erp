<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehicleMaintenanceRecord extends Model
{
    use SoftDeletes;

    protected $table = 'hr_vehicle_maintenance_records';

    protected $fillable = [
        'vehicle_id',
        'asset_id',
        'service_request_id',
        'service_type_option_id',
        'provider_option_id',
        'service_date',
        'odometer_km',
        'cost',
        'next_service_odometer',
        'next_service_date',
        'report_path',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'service_date' => 'date',
            'next_service_date' => 'date',
            'cost' => 'decimal:2',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(VehicleServiceRequest::class, 'service_request_id');
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(VehicleSetting::class, 'service_type_option_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(VehicleSetting::class, 'provider_option_id');
    }

    protected static function booted(): void
    {
        static::saved(function (VehicleMaintenanceRecord $vm) {
            $mType = \App\Models\MaintenanceType::firstOrCreate(['name' => 'Vehicle Service']);

            \App\Models\Maintenance::updateOrCreate(
                ['notes' => 'V-REC-' . $vm->id], // Identifier
                [
                    'asset_id' => $vm->asset_id ?: Asset::where('asset_tag', 'VEH-' . $vm->vehicle_id)->value('id'),
                    'maintenance_type_id' => $mType->id,
                    'title' => 'Vehicle Service Record: ' . ($vm->serviceType?->label ?: 'Service'),
                    'description' => $vm->notes,
                    'start_date' => $vm->service_date,
                    'next_scheduled_date' => $vm->next_service_date,
                    'cost' => $vm->cost,
                    'notes' => 'V-REC-' . $vm->id . (filled($vm->notes) ? "\n" . $vm->notes : ''),
                ]
            );
        });

        static::deleted(function (VehicleMaintenanceRecord $vm) {
            \App\Models\Maintenance::where('notes', 'like', 'V-REC-' . $vm->id . '%')->delete();
        });
    }
}


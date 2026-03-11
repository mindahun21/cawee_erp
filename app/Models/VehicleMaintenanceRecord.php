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
        return $this->belongsTo(Asset::class, 'asset_id');
    }

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(VehicleServiceRequest::class, 'service_request_id');
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(HrSettingOption::class, 'service_type_option_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(HrSettingOption::class, 'provider_option_id');
    }
}


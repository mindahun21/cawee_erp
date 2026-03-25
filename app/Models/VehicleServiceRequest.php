<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehicleServiceRequest extends Model
{
    use SoftDeletes;

    protected $table = 'hr_vehicle_service_requests';

    protected $fillable = [
        'vehicle_id',
        'asset_id',
        'service_type_option_id',
        'urgency_option_id',
        'provider_option_id',
        'problem_description',
        'requested_by',
        'requested_at',
        'status',
        'approved_by',
        'approved_at',
        'service_report_path',
        'completed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'approved_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $request): void {
            if (empty($request->requested_at)) {
                $request->requested_at = now();
            }

            if (empty($request->requested_by) && auth()->check()) {
                $request->requested_by = auth()->id();
            }
        });
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(VehicleSetting::class, 'service_type_option_id');
    }

    public function urgencyLevel(): BelongsTo
    {
        return $this->belongsTo(VehicleSetting::class, 'urgency_option_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(VehicleSetting::class, 'provider_option_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function maintenanceRecord(): HasOne
    {
        return $this->hasOne(VehicleMaintenanceRecord::class, 'service_request_id');
    }
}


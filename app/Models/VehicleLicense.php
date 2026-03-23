<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehicleLicense extends Model
{
    use SoftDeletes;

    protected $table = 'hr_vehicle_licenses';

    protected $fillable = [
        'vehicle_id',
        'asset_id',
        'license_number',
        'bolo_issue_date',
        'bolo_expiry_date',
        'receipt_path',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'bolo_issue_date' => 'date',
            'bolo_expiry_date' => 'date',
        ];
    }

    public function getDaysUntilExpiryAttribute(): ?int
    {
        if (! $this->bolo_expiry_date) {
            return null;
        }

        return (int) now()->diffInDays($this->bolo_expiry_date, false);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }
}


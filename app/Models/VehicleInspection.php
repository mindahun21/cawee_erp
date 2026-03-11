<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehicleInspection extends Model
{
    use SoftDeletes;

    protected $table = 'hr_vehicle_inspections';

    protected $fillable = [
        'asset_id',
        'inspection_date',
        'inspection_expiry_date',
        'certificate_path',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'inspection_date' => 'date',
            'inspection_expiry_date' => 'date',
        ];
    }

    public function getDaysUntilExpiryAttribute(): ?int
    {
        if (! $this->inspection_expiry_date) {
            return null;
        }

        return (int) now()->diffInDays($this->inspection_expiry_date, false);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }
}


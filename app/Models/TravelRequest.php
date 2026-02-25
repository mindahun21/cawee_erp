<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TravelRequest extends Model
{
    protected $fillable = [
        'employee_id',
        'travel_type',
        'start_date',
        'end_date',
        'per_diem_amount',
        'vehicle_required',
        'approval_status',
        'report_submitted',
    ];

    protected $casts = [
        'start_date'       => 'date',
        'end_date'         => 'date',
        'per_diem_amount'  => 'decimal:2',
        'vehicle_required' => 'boolean',
        'report_submitted' => 'boolean',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function getTotalDiemAttribute(): float
    {
        $days = $this->start_date->diffInDays($this->end_date) + 1;

        return $days * (float) $this->per_diem_amount;
    }
}

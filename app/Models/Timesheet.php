<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Timesheet extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id', 'location_id', 'month', 'year',
        'status', 'approved_by', 'approved_at', 'remarks',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approved_by');
    }

    public function rows(): HasMany
    {
        return $this->hasMany(TimesheetRow::class);
    }

    public function leaveRows(): HasMany
    {
        return $this->hasMany(TimesheetLeaveRow::class);
    }

    public function getTotalWorkHoursAttribute(): float
    {
        return (float) $this->rows->sum('total_hours');
    }
}

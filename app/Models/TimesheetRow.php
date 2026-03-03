<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimesheetRow extends Model
{
    protected $fillable = [
        'timesheet_id', 'project_id', 'work_site', 'daily_hours', 'total_hours',
    ];

    protected function casts(): array
    {
        return [
            'daily_hours' => 'array',
        ];
    }

    public function timesheet(): BelongsTo
    {
        return $this->belongsTo(Timesheet::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    // Auto-compute total from daily_hours before save
    protected static function booted(): void
    {
        static::saving(function (self $row) {
            $row->total_hours = array_sum($row->daily_hours ?? []);
        });
    }
}

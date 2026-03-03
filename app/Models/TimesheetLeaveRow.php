<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimesheetLeaveRow extends Model
{
    protected $fillable = [
        'timesheet_id', 'leave_type', 'daily_flags', 'total_days',
    ];

    protected function casts(): array
    {
        return [
            'daily_flags' => 'array',
        ];
    }

    public function timesheet(): BelongsTo
    {
        return $this->belongsTo(Timesheet::class);
    }

    protected static function booted(): void
    {
        static::saving(function (self $row) {
            $row->total_days = array_sum($row->daily_flags ?? []);
        });
    }
}

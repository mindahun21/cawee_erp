<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrTimesheetEntry extends Model
{
    protected $table = 'hr_timesheet_entries';

    protected $fillable = [
        'hr_timesheet_id',
        'project_id',
        'location_id',
        'day',
        'hours',
        'description',
    ];

    public function timesheet(): BelongsTo
    {
        return $this->belongsTo(HrTimesheet::class, 'hr_timesheet_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }
}

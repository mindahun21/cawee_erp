<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrTimesheetLeave extends Model
{
    protected $table = 'hr_timesheet_leaves';

    protected $fillable = [
        'hr_timesheet_id',
        'hr_leave_type_id',
        'day',
        'hours',
    ];

    public function timesheet(): BelongsTo
    {
        return $this->belongsTo(HrTimesheet::class, 'hr_timesheet_id');
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(HrLeaveType::class, 'hr_leave_type_id');
    }
}

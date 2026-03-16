<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HrLeaveType extends Model
{
    protected $table = 'hr_leave_types';

    protected $fillable = [
        'name',
        'is_active',
        'holiday_date',
        'is_recurring',
        'description',
    ];

    protected $casts = [
        'holiday_date' => 'date',
        'is_recurring' => 'boolean',
        'is_active' => 'boolean',
    ];
}

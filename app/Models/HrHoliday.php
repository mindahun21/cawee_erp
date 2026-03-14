<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HrHoliday extends Model
{
    protected $table = 'hr_holidays';

    protected $fillable = [
        'name',
        'holiday_date',
        'is_recurring',
        'description',
    ];

    protected $casts = [
        'holiday_date' => 'date',
        'is_recurring' => 'boolean',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeRecord extends Model
{
    protected $fillable = [
        'employee_id',
        'project_id',
        'work_date',
        'hours_worked',
        'leave_type',
        'remarks',
    ];

    protected $casts = [
        'work_date'    => 'date',
        'hours_worked' => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}

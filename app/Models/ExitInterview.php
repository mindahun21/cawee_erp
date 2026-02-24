<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExitInterview extends Model
{
    protected $fillable = [
        'employee_id', 'termination_date', 'starting_position', 'ending_position',
        'liked_most', 'liked_least', 'reason_for_leaving', 'ratings',
        'additional_comments', 'interviewer_id', 'interview_date',
    ];

    protected function casts(): array
    {
        return [
            'termination_date' => 'date',
            'interview_date'   => 'date',
            'ratings'          => 'array',
        ];
    }

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function interviewer(): BelongsTo { return $this->belongsTo(Employee::class, 'interviewer_id'); }
}

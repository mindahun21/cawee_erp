<?php

namespace App\Models\Recruitment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecruitmentScheduleCandidate extends Model
{
    protected $table = 'recruitment_schedule_candidates';

    protected $fillable = [
        'schedule_id',
        'candidate_id',
        'candidate_from_time',
        'candidate_to_time',
    ];

    protected $casts = [
        // Times handled as strings for validation simplicity in Filament for now
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(RecruitmentInterviewSchedule::class, 'schedule_id');
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(RecruitmentCandidate::class, 'candidate_id');
    }
}

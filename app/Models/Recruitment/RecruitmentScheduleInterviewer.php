<?php

namespace App\Models\Recruitment;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecruitmentScheduleInterviewer extends Model
{
    protected $table = 'recruitment_schedule_interviewers';

    protected $fillable = [
        'schedule_id',
        'user_id',
        'role',
        'notes',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(RecruitmentInterviewSchedule::class, 'schedule_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

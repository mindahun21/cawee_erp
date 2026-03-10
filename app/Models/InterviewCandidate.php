<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterviewCandidate extends Model
{


    public function schedule()
    {
        return $this->belongsTo(RecruitmentInterview::class);
    }

    public function interview()
    {
        return $this->belongsTo(RecruitmentInterview::class, 'recruitment_interview_id');
    }
}

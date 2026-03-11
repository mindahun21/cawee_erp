<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterviewCandidate extends Model
{

    protected $guarded = [];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];
    public function schedule()
    {
        return $this->belongsTo(RecruitmentInterview::class);
    }

    public function interview()
    {
        return $this->belongsTo(RecruitmentInterview::class, 'recruitment_interview_id');
    }

    public function candidate()
    {
        return $this->belongsTo(RecruitmentCandidate::class);
    }
}

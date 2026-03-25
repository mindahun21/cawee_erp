<?php

namespace App\Models\Recruitment;

use Illuminate\Database\Eloquent\Relations\Pivot;

class RecruitmentCandidateSkill extends Pivot
{
    protected $table = 'recruitment_candidate_skill';

    public $incrementing = false;
    
    protected $casts = [
        'proficiency' => 'integer',
    ];
}

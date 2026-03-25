<?php

namespace App\Models\Recruitment;

use Illuminate\Database\Eloquent\Relations\Pivot;

class RecruitmentCampaignSkill extends Pivot
{
    protected $table = 'recruitment_campaign_skill';

    public $incrementing = true;
    
    protected $casts = [
        'is_required' => 'boolean',
        'min_proficiency' => 'integer',
    ];
}

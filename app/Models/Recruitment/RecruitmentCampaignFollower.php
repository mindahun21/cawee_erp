<?php

namespace App\Models\Recruitment;

use Illuminate\Database\Eloquent\Relations\Pivot;

class RecruitmentCampaignFollower extends Pivot
{
    protected $table = 'recruitment_campaign_followers';

    public $timestamps = false;
    public $incrementing = false;
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class recruitmentInterview extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
    public function candidates()
    {
        return $this->hasMany(InterviewCandidate::class);
    }
    public function recruitmentCampaign()
    {
        return $this->belongsTo(RecruitmentCampaign::class);
    }
    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function recruitmentPosition()
    {
        return $this->belongsTo(RecruitmentPosition::class, 'recruitment_position_id');
    }
}

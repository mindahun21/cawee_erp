<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecruitmentCandidate extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function seniorities()
    {
        return $this->hasMany(CandidateSeniority::class, 'candidate_id');
    }

    public function educations()
    {
        return $this->hasMany(CandidateEducation::class, 'candidate_id');
    }

    public function references()
    {
        return $this->hasMany(CandidateReference::class, 'candidate_id');
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(RecruitmentCampaign::class, 'recruitment_campaign_id');
    }
}

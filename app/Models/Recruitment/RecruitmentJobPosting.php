<?php

namespace App\Models\Recruitment;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecruitmentJobPosting extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'recruitment_job_postings';

    protected $fillable = [
        'job_position_id',
        'campaign_id',
        'recruitment_plan_id',
        'channel_id',
        'title',
        'description',
        'requirements',
        'location',
        'employment_type',
        'posted_date',
        'closing_date',
        'status',
        'is_public',
        'created_by',
    ];

    protected $casts = [
        'posted_date' => 'date',
        'closing_date' => 'date',
        'is_public' => 'boolean',
    ];

    public function jobPosition(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\JobPosition::class);
    }

    public function campaign(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(RecruitmentCampaign::class);
    }

    public function plan(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(RecruitmentPlan::class, 'recruitment_plan_id');
    }

    public function channel(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(RecruitmentChannel::class, 'channel_id');
    }

    public function creator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function applications(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RecruitmentApplication::class);
    }

    /**
     * Posting-specific skills.
     */
    public function skills(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            RecruitmentSkill::class,
            'recruitment_job_posting_skills',
            'job_posting_id',
            'recruitment_skill_id'
        )->withPivot(['is_required', 'min_proficiency'])
         ->withTimestamps();
    }
}

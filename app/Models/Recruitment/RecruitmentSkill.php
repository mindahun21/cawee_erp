<?php

namespace App\Models\Recruitment;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecruitmentSkill extends Model
{
    use HasFactory;

    protected $table = 'recruitment_skills';

    protected $fillable = [
        'name',
        'category',
    ];

    /**
     * Job Positions requiring this skill.
     */
    public function jobPositions(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            \App\Models\JobPosition::class,
            'recruitment_job_position_skills',
            'recruitment_skill_id',
            'job_position_id'
        )->withPivot(['is_required', 'min_proficiency'])
         ->withTimestamps();
    }

    /**
     * Job Postings requiring this skill.
     */
    public function jobPostings(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            RecruitmentJobPosting::class,
            'recruitment_job_posting_skills',
            'recruitment_skill_id',
            'job_posting_id'
        )->withPivot(['is_required', 'min_proficiency'])
         ->withTimestamps();
    }

    /**
     * Candidates possessing this skill.
     */
    public function candidates(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            RecruitmentCandidate::class,
            'recruitment_candidate_skills',
            'recruitment_skill_id',
            'candidate_id'
        )->withPivot(['proficiency'])
         ->withTimestamps();
    }
}

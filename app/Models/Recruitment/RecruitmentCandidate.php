<?php

namespace App\Models\Recruitment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecruitmentCandidate extends Model
{
    use SoftDeletes;

    protected $table = 'recruitment_candidates';

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'resume_path',
        'linkedin_url',
        'source_channel',
        'channel_id',
        'skills',
        'notes',
    ];

    protected $casts = [
        'skills' => 'json',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function channel(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(RecruitmentChannel::class, 'channel_id');
    }

    public function applications(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RecruitmentApplication::class, 'candidate_id');
    }
    /**
     * Candidate skills profile.
     */
    public function skills(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            RecruitmentSkill::class,
            'recruitment_candidate_skills',
            'candidate_id',
            'recruitment_skill_id'
        )->withPivot(['proficiency'])
         ->withTimestamps();
    }
}

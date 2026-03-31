<?php

namespace App\Models\Recruitment;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecruitmentCandidate extends Authenticatable
{
    use HasFactory, SoftDeletes;

    protected $table = 'recruitment_candidates';
    protected $guarded = [];

    protected $casts = [
        'birthday' => 'date',
        'days_for_identity' => 'date',
        'skills_snapshot' => 'array',
        'portal_access' => 'boolean',
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected static function booted()
    {
        static::creating(function ($candidate) {
            if (empty($candidate->candidate_code)) {
                // Use withTrashed() to ensure we account for soft-deleted records when generating the next ID-based code
                $last = static::withTrashed()->orderBy('id', 'desc')->first();
                $next = $last ? $last->id + 1 : 1;
                $candidate->candidate_code = 'ID' . str_pad($next, 5, '0', STR_PAD_LEFT);
            }
        });
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(RecruitmentApplication::class, 'candidate_id');
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(RecruitmentCandidateEvaluation::class, 'candidate_id');
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(RecruitmentSkill::class, 'recruitment_candidate_skill', 'candidate_id', 'recruitment_skill_id')
                    ->using(RecruitmentCandidateSkill::class)
                    ->withPivot('proficiency')
                    ->withTimestamps();
    }

    public function seniorities(): HasMany
    {
        return $this->hasMany(RecruitmentCandidateSeniority::class, 'candidate_id');
    }

    public function literacies(): HasMany
    {
        return $this->hasMany(RecruitmentCandidateLiteracy::class, 'candidate_id');
    }

    public function references(): HasMany
    {
        return $this->hasMany(RecruitmentCandidateReference::class, 'candidate_id');
    }
}

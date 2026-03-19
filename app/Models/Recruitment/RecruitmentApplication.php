<?php

namespace App\Models\Recruitment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecruitmentApplication extends Model
{
    use SoftDeletes;

    protected $table = 'recruitment_applications';

    protected $fillable = [
        'candidate_id',
        'job_posting_id',
        'status',
        'applied_at',
        'reviewed_by',
        'shortlisted_by',
        'rejection_reason',
        'internal_notes',
    ];

    protected $casts = [
        'applied_at' => 'datetime',
    ];

    public function candidate(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(RecruitmentCandidate::class);
    }

    public function jobPosting(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(RecruitmentJobPosting::class);
    }

    public function reviewer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'reviewed_by');
    }

    public function shortlister(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'shortlisted_by');
    }

    public function interviews(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RecruitmentInterview::class, 'application_id');
    }

    public function offer(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(RecruitmentOffer::class, 'application_id');
    }
}

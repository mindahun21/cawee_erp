<?php

namespace App\Models\Recruitment;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecruitmentCandidateEvaluation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'recruitment_candidate_evaluations';

    protected $fillable = [
        'schedule_id',
        'candidate_id',
        'interviewer_id',
        'template_id',
        'overall_score',
        'comments',
    ];

    protected $casts = [
        'overall_score' => 'decimal:2',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(RecruitmentInterviewSchedule::class, 'schedule_id');
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(RecruitmentCandidate::class, 'candidate_id');
    }

    public function interviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'interviewer_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(RecruitmentEvaluationFormTemplate::class, 'template_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(RecruitmentEvaluationScore::class, 'evaluation_id');
    }
}

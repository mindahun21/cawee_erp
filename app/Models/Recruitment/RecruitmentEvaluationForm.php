<?php

namespace App\Models\Recruitment;

use Illuminate\Database\Eloquent\Model;

class RecruitmentEvaluationForm extends Model
{
    protected $table = 'recruitment_evaluation_forms';

    protected $fillable = [
        'interview_id',
        'evaluator_id',
        'total_score',
        'recommendation',
        'comments',
        'submitted_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'total_score' => 'decimal:2',
    ];

    public function interview(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(RecruitmentInterview::class);
    }

    public function evaluator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'evaluator_id');
    }

    public function scores(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RecruitmentEvaluationScore::class, 'form_id');
    }
}

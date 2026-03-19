<?php

namespace App\Models\Recruitment;

use Illuminate\Database\Eloquent\Model;

class RecruitmentEvaluationScore extends Model
{
    protected $table = 'recruitment_evaluation_scores';

    protected $fillable = [
        'form_id',
        'criteria_id',
        'score',
        'comment',
    ];

    public function form(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(RecruitmentEvaluationForm::class);
    }

    public function criteria(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(RecruitmentEvaluationCriteria::class, 'criteria_id');
    }
}

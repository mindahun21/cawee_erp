<?php

namespace App\Models\Recruitment;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecruitmentEvaluationScore extends Model
{
    use HasFactory;

    protected $table = 'recruitment_evaluation_scores';

    protected $fillable = [
        'evaluation_id',
        'criteria_id',
        'score',
        'comment',
    ];

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(RecruitmentCandidateEvaluation::class, 'evaluation_id');
    }

    public function criteria(): BelongsTo
    {
        return $this->belongsTo(RecruitmentEvaluationCriteria::class, 'criteria_id');
    }
}

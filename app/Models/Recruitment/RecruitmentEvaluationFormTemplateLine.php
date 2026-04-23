<?php

namespace App\Models\Recruitment;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecruitmentEvaluationFormTemplateLine extends Model
{
    use HasFactory;

    protected $table = 'recruitment_evaluation_form_template_lines';

    protected $fillable = [
        'template_id',
        'group_criteria_id',
        'criteria_id',
        'proportion',
        'sort_order',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(RecruitmentEvaluationFormTemplate::class, 'template_id');
    }

    public function groupCriteria(): BelongsTo
    {
        return $this->belongsTo(RecruitmentEvaluationCriteria::class, 'group_criteria_id');
    }

    public function criteria(): BelongsTo
    {
        return $this->belongsTo(RecruitmentEvaluationCriteria::class, 'criteria_id');
    }
}

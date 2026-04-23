<?php

namespace App\Models\Recruitment;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;

class RecruitmentEvaluationCriteria extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'recruitment_evaluation_criterias';

    protected $fillable = [
        'name',
        'description',
        'criteria_type',
        'score_1_desc',
        'score_2_desc',
        'score_3_desc',
        'score_4_desc',
        'score_5_desc',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // A criteria could be used as the group or the child item.
    public function templateLinesWhereGroup(): HasMany
    {
        return $this->hasMany(RecruitmentEvaluationFormTemplateLine::class, 'group_criteria_id');
    }

    public function templateLinesWhereCriteria(): HasMany
    {
        return $this->hasMany(RecruitmentEvaluationFormTemplateLine::class, 'criteria_id');
    }
}

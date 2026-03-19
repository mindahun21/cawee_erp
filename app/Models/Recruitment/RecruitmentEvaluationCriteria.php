<?php

namespace App\Models\Recruitment;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecruitmentEvaluationCriteria extends Model
{
    use HasFactory;

    protected $table = 'recruitment_evaluation_criterias';

    protected $fillable = [
        'name',
        'description',
        'weight',
    ];
}

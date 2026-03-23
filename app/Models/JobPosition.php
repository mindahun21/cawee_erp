<?php

namespace App\Models;

use App\Models\Recruitment\RecruitmentSkill;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobPosition extends Model
{
    protected $table = 'hr_job_positions';

    protected $fillable = [
        'department_id', 
        'title', 
        'grade_id',
        'description', 
        'requirements', 
        'salary_min', 
        'salary_max', 
        'vacancy_count', 
        'is_active'
    ];

    protected $casts = [
    'is_active' => 'boolean',
    'vacancy_count' => 'integer',
    'salary_min' => 'decimal:2',
    'salary_max' => 'decimal:2',
];

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(
            RecruitmentSkill::class,
            'recruitment_job_position_skill',
            'job_position_id',
            'recruitment_skill_id'
        );
    }
}

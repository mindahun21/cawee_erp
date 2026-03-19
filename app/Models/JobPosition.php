<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    /**
     * Required skills for this job position.
     */
    public function skills(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            Recruitment\RecruitmentSkill::class,
            'recruitment_job_position_skills',
            'job_position_id',
            'recruitment_skill_id'
        )->withPivot(['is_required', 'min_proficiency'])
         ->withTimestamps();
    }
}

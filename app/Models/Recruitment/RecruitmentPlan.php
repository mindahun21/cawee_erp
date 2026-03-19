<?php

namespace App\Models\Recruitment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecruitmentPlan extends Model
{
    use SoftDeletes;

    protected $table = 'recruitment_plans';

    protected $fillable = [
        'department_id',
        'job_position_id',
        'vacancies_needed',
        'expected_hire_date',
        'budget',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
        'notes',
    ];

    protected $casts = [
        'expected_hire_date' => 'date',
        'approved_at' => 'datetime',
        'budget' => 'decimal:2',
    ];

    public function department(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Department::class);
    }

    public function jobPosition(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\JobPosition::class);
    }

    public function creator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function approver(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function jobPostings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RecruitmentJobPosting::class);
    }
}

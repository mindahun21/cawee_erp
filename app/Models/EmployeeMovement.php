<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeMovement extends Model
{
    protected $table = 'hr_employee_movements';

    protected $fillable = [
        'employee_id',
        'movement_type',
        'from_department_id', 'to_department_id',
        'from_job_position_id', 'to_job_position_id',
        'from_salary', 'to_salary',
        'from_salary_grade_id', 'to_salary_grade_id',
        'effective_date',
        'approved_by',
        'approved_at',
        'rejected_at',
        'rejection_reason',
        'reference_number',
        'reason',
        'status',
        'attachment_path',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'approved_at'    => 'datetime',
        'rejected_at'    => 'datetime',
        'from_salary'    => 'decimal:2',
        'to_salary'      => 'decimal:2',
    ];

    public function isPending(): bool  { return $this->status === 'Pending Approval'; }
    public function isApproved(): bool { return $this->status === 'Approved'; }
    public function isRejected(): bool { return $this->status === 'Rejected'; }

    public function employee(): BelongsTo      { return $this->belongsTo(Employee::class); }
    public function fromDepartment(): BelongsTo { return $this->belongsTo(Department::class, 'from_department_id'); }
    public function toDepartment(): BelongsTo   { return $this->belongsTo(Department::class, 'to_department_id'); }
    public function fromPosition(): BelongsTo   { return $this->belongsTo(JobPosition::class, 'from_job_position_id'); }
    public function toPosition(): BelongsTo     { return $this->belongsTo(JobPosition::class, 'to_job_position_id'); }
    public function fromGrade(): BelongsTo      { return $this->belongsTo(SalaryGrade::class, 'from_salary_grade_id'); }
    public function toGrade(): BelongsTo        { return $this->belongsTo(SalaryGrade::class, 'to_salary_grade_id'); }
    public function approver(): BelongsTo       { return $this->belongsTo(\App\Models\User::class, 'approved_by'); }
}

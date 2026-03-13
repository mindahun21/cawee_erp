<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'first_name', 'last_name', 'gender', 'date_of_birth',
        'national_id', 'tin', 'pension_id', 'phone_number', 'email',
        'education_level', 'field_of_study',
        'extra_attributes',
        'position', 'employment_type',
        'department_id', 'job_position_id', 'contract_type_id',
        'education_level_id', 'field_of_study_id',
        'date_of_employment', 'date_transferred', 'date_resigned',
        'basic_salary', 'transport_allowance', 'house_allowance',
        'communication_allowance', 'overtime_allowance', 'incentive', 'other_allowances',
        'salary_grade_id', 'grade', 'step',
        'bank_account_awash', 'bank_account_orocoop', 'bank_account_other',
        'remarks', 'location_id', 'project_id',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth'           => 'date',
            'date_of_employment'      => 'date',
            'date_transferred'        => 'date',
            'date_resigned'           => 'date',
            'basic_salary'            => 'decimal:2',
            'transport_allowance'     => 'decimal:2',
            'house_allowance'         => 'decimal:2',
            'communication_allowance' => 'decimal:2',
            'overtime_allowance'      => 'decimal:2',
            'incentive'               => 'decimal:2',
            'other_allowances'        => 'decimal:2',
            'extra_attributes'        => 'array',
        ];
    }

    // ── Computed ───────────────────────────────────────────────────
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getTotalCompensationAttribute(): float
    {
        return (float) $this->basic_salary
            + (float) $this->transport_allowance
            + (float) $this->house_allowance
            + (float) $this->communication_allowance
            + (float) $this->overtime_allowance
            + (float) $this->incentive
            + (float) $this->other_allowances;
    }

    // ── Lookups ────────────────────────────────────────────────────
    public function location(): BelongsTo    { return $this->belongsTo(Location::class); }
    public function project(): BelongsTo     { return $this->belongsTo(Project::class); }
    public function salaryGrade(): BelongsTo { return $this->belongsTo(SalaryGrade::class); }

    // ── HR Settings ────────────────────────────────────────────────
    public function user(): BelongsTo           { return $this->belongsTo(\Illuminate\Foundation\Auth\User::class); }
    public function department(): BelongsTo    { return $this->belongsTo(Department::class); }
    public function jobPosition(): BelongsTo   { return $this->belongsTo(JobPosition::class); }
    public function contractType(): BelongsTo  { return $this->belongsTo(ContractType::class); }
    public function educationLevel(): BelongsTo { return $this->belongsTo(EducationLevel::class); }
    public function fieldOfStudy(): BelongsTo  { return $this->belongsTo(FieldOfStudy::class); }

    // ── Leave ──────────────────────────────────────────────────────
    public function leaveRequests(): HasMany { return $this->hasMany(LeaveRequest::class); }
    public function leaveBalance(): HasMany  { return $this->hasMany(LeaveBalance::class); }

    // ── Time ───────────────────────────────────────────────────────
    public function timeRecords(): HasMany { return $this->hasMany(TimeRecord::class); }
    public function timesheets(): HasMany  { return $this->hasMany(Timesheet::class); }

    // ── Appraisals / Performance ───────────────────────────────────
    public function performanceEvaluations(): HasMany { return $this->hasMany(PerformanceEvaluation::class); }
    public function appraisals(): HasMany             { return $this->hasMany(Appraisal::class); }

    // ── Travel ─────────────────────────────────────────────────────
    public function travelRequests(): HasMany { return $this->hasMany(TravelRequest::class); }
    public function travelAdvances(): HasMany { return $this->hasMany(TravelAdvance::class); }

    // ── HR Processes ───────────────────────────────────────────────
    public function hrProcesses(): HasMany { return $this->hasMany(HrProcess::class); }

    // ── Payroll ────────────────────────────────────────────────────
    public function payrollRecords(): HasMany { return $this->hasMany(Payroll::class); }

    // ── Onboarding / Offboarding ───────────────────────────────────
    public function onboarding(): HasMany     { return $this->hasMany(EmployeeOnboarding::class); }
    public function exitInterview(): HasMany  { return $this->hasMany(ExitInterview::class); }
    public function clearanceForms(): HasMany { return $this->hasMany(ClearanceForm::class); }

    // ── Contracts / Dependents / Trainings ─────────────────────────
    public function contracts(): HasMany   { return $this->hasMany(EmployeeContract::class); }
    public function dependents(): HasMany  { return $this->hasMany(Dependent::class); }
    public function trainings(): HasMany   { return $this->hasMany(Training::class); }

    // ── Movements (Promotion / Demotion / Transfer) ─────────────────
    public function movements(): HasMany            { return $this->hasMany(EmployeeMovement::class); }

    // ── Asset Assignments ──────────────────────────────────────────
    public function assetAssignments(): HasMany { return $this->hasMany(AssetAssignment::class); }

    // ── Delegations ─────────────────────────────────────────────────
    public function delegationsGiven(): HasMany    { return $this->hasMany(Delegation::class, 'delegator_id'); }
    public function delegationsReceived(): HasMany { return $this->hasMany(Delegation::class, 'delegate_id'); }

    public function warehouses(): BelongsToMany
    {
        return $this->belongsToMany(Warehouse::class, 'warehouse_employee');
    }
}

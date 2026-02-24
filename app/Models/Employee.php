<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'first_name', 'last_name', 'gender', 'date_of_birth',
        'national_id', 'tin', 'pension_id', 'phone_number', 'email',
        'education_level', 'field_of_study',
        'extra_attributes',
        'position', 'employment_type',
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
    public function location(): BelongsTo   { return $this->belongsTo(Location::class); }
    public function project(): BelongsTo    { return $this->belongsTo(Project::class); }
    public function salaryGrade(): BelongsTo { return $this->belongsTo(SalaryGrade::class); }

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
}

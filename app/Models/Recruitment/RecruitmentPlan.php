<?php

namespace App\Models\Recruitment;

use App\Contracts\Recruitment\Approvable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Models\Department;
use App\Models\JobPosition;
use App\Models\User;

class RecruitmentPlan extends Model implements Approvable
{
    use SoftDeletes;

    const STATUS_DRAFT     = 'draft';
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_APPROVED  = 'approved';
    const STATUS_REJECTED  = 'rejected';
    const STATUS_CLOSED    = 'closed';

    protected $table = 'recruitment_plans';

    protected $fillable = [
        'title',
        'department_id',
        'job_position_id',
        'manager_id',
        'vacancies_needed',
        'working_from',
        'workplace',
        'salary_from',
        'salary_to',
        'salary_currency',
        'start_date',
        'end_date',
        'budget',
        'reason',
        'job_description',
        'approval_workflow_id',
        'status',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'start_date'      => 'date',
        'end_date'        => 'date',
        'budget'          => 'decimal:2',
        'salary_from'     => 'decimal:2',
        'salary_to'       => 'decimal:2',
        'vacancies_needed' => 'integer',
    ];

    /* ── Relationships ── */

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function jobPosition(): BelongsTo
    {
        return $this->belongsTo(JobPosition::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvalWorkflow(): BelongsTo
    {
        return $this->belongsTo(RecruitmentApprovalWorkflow::class, 'approval_workflow_id');
    }

    public function approvalRecords(): MorphMany
    {
        return $this->morphMany(RecruitmentApprovalRecord::class, 'approvable');
    }

    /* ── Approvable Interface ── */

    public function approvalDocumentType(): string
    {
        return 'recruitment_plan';
    }

    public function approvedStatus(): string
    {
        return self::STATUS_APPROVED;
    }

    public function rejectedStatus(): string
    {
        return self::STATUS_DRAFT;
    }

    public function submittedStatus(): string
    {
        return self::STATUS_SUBMITTED;
    }

    public function draftStatus(): string
    {
        return self::STATUS_DRAFT;
    }

    public function onFullyApproved(): void
    {
        // Plan stays at STATUS_APPROVED (set by service)
    }

    public function onRejected(): void
    {
        $this->update(['status' => $this->rejectedStatus()]);
    }

    /* ── Helpers ── */

    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_REJECTED]);
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_DRAFT     => 'Draft',
            self::STATUS_SUBMITTED => 'Submitted',
            self::STATUS_APPROVED  => 'Approved',
            self::STATUS_REJECTED  => 'Rejected',
            self::STATUS_CLOSED    => 'Closed',
            default                => $status,
        };
    }

    public static function statusColor(string $status): string
    {
        return match ($status) {
            self::STATUS_DRAFT     => 'gray',
            self::STATUS_SUBMITTED => 'warning',
            self::STATUS_APPROVED  => 'success',
            self::STATUS_REJECTED  => 'danger',
            self::STATUS_CLOSED    => 'info',
            default                => 'secondary',
        };
    }

    public static function workingFromOptions(): array
    {
        return [
            'Internship' => 'Internship',
            'Full-Time'  => 'Full-Time',
            'Part-Time'  => 'Part-Time',
            'Contract'   => 'Contract',
            'Temporary'  => 'Temporary',
        ];
    }

    public static function currencyOptions(): array
    {
        return [
            'ETB' => 'ETB (Birr)',
            'USD' => 'USD (Dollar)',
            'EUR' => 'EUR (Euro)',
        ];
    }
}

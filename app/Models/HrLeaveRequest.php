<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HrLeaveRequest extends Model
{
    use SoftDeletes;

    protected $table = 'hr_leave_requests';

    const STATUS_PENDING  = 'Pending';
    const STATUS_APPROVED = 'Approved';
    const STATUS_REJECTED = 'Rejected';

    protected $fillable = [
        'employee_id',
        'hr_leave_type_id',
        'start_date',
        'end_date',
        'no_of_days',
        'from_time',
        'to_time',
        'total_hours',
        'reason',
        'remarks',
        'supporting_document',

        // Overall
        'approval_status',
        'approval_date',

        // Stage 1 — Supervisor
        'supervisor_status',
        'supervisor_approved_by',
        'supervisor_approved_at',

        // Stage 2 — HR
        'hr_status',
        'hr_approved_by',
        'hr_approved_at',

        // Stage 3 — Director
        'director_status',
        'director_approved_by',
        'director_approved_at',

        // Import
        'is_imported',
        'import_fiscal_year',
    ];

    protected $casts = [
        'start_date'            => 'date',
        'end_date'              => 'date',
        'approval_date'         => 'date',
        'supervisor_approved_at'=> 'datetime',
        'hr_approved_at'        => 'datetime',
        'director_approved_at'  => 'datetime',
        'no_of_days'            => 'integer',
        'total_hours'           => 'float',
        'is_imported'           => 'boolean',
        'import_fiscal_year'    => 'integer',
    ];

    // ── Relationships ───────────────────────────────────────────────

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(HrLeaveType::class, 'hr_leave_type_id');
    }

    public function supervisorApprover(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'supervisor_approved_by');
    }

    public function hrApprover(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'hr_approved_by');
    }

    public function directorApprover(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'director_approved_by');
    }

    // ── Computed ────────────────────────────────────────────────────

    public function getDurationDaysAttribute(): int
    {
        if (! $this->start_date || ! $this->end_date) {
            return 0;
        }
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    // ── Workflow Helpers ─────────────────────────────────────────────

    public function isFullyApproved(): bool
    {
        return $this->approval_status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return in_array(self::STATUS_REJECTED, [
            $this->supervisor_status,
            $this->hr_status,
            $this->director_status,
            $this->approval_status,
        ]);
    }

    public function canSupervisorApprove(): bool
    {
        return $this->supervisor_status === self::STATUS_PENDING
            && $this->approval_status  === self::STATUS_PENDING;
    }

    public function canHrApprove(): bool
    {
        return $this->supervisor_status === self::STATUS_APPROVED
            && $this->hr_status         === self::STATUS_PENDING
            && $this->approval_status   === self::STATUS_PENDING;
    }

    public function canDirectorApprove(): bool
    {
        return $this->hr_status        === self::STATUS_APPROVED
            && $this->director_status  === self::STATUS_PENDING
            && $this->approval_status  === self::STATUS_PENDING;
    }

    public function getCurrentStageAttribute(): string
    {
        if ($this->isRejected())                              return 'Rejected';
        if ($this->isFullyApproved())                         return 'Fully Approved';
        if ($this->hr_status === self::STATUS_APPROVED)       return 'Awaiting Director';
        if ($this->supervisor_status === self::STATUS_APPROVED) return 'Awaiting HR';
        return 'Awaiting Supervisor';
    }
}

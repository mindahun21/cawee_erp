<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HrLeaveRequest extends Model
{
    use SoftDeletes;

    protected $table = 'hr_leave_requests';

    // Overall workflow status
    const STATUS_PENDING   = 'Pending';
    const STATUS_APPROVED  = 'Approved';
    const STATUS_REJECTED  = 'Rejected';

    protected $fillable = [
        'employee_id',
        'hr_leave_type_id',
        'start_date',
        'end_date',
        'reason',
        'status', // legacy/simple status
        'supervisor_id', // legacy simple approver
        'approved_at', // legacy simple approval time

        'supporting_document',
        'remarks',

        // Stage 1 — Supervisor
        'supervisor_approved_by',
        'supervisor_approved_at',
        'supervisor_status',

        // Stage 2 — HR
        'hr_approved_by',
        'hr_approved_at',
        'hr_status',

        // Stage 3 — Director
        'director_approved_by',
        'director_approved_at',
        
        'approval_status', // Overall status
        'approval_date',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
        'supervisor_approved_at' => 'datetime',
        'hr_approved_at' => 'datetime',
        'director_approved_at' => 'datetime',
        'approval_date' => 'date',
    ];

    // ── Relationships ──────────────────────────────────────────────
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(HrLeaveType::class, 'hr_leave_type_id');
    }

    public function legacySupervisor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'supervisor_id');
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

    // ── Computed ───────────────────────────────────────────────────
    public function getDurationInDaysAttribute(): float
    {
        if (!$this->start_date || !$this->end_date) {
            return 0;
        }
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    public function getOverallStatusAttribute(): string
    {
        if ($this->supervisor_status === 'Rejected' || $this->hr_status === 'Rejected' || $this->approval_status === 'Rejected') {
            return self::STATUS_REJECTED;
        }
        if ($this->director_approved_at !== null || $this->approval_status === 'Approved') {
            return self::STATUS_APPROVED;
        }
        return self::STATUS_PENDING;
    }

    public function getCurrentStageAttribute(): string
    {
        if ($this->supervisor_status === 'Rejected' || $this->hr_status === 'Rejected' || $this->approval_status === 'Rejected') {
            return 'Rejected';
        }
        if ($this->director_approved_at || $this->approval_status === 'Approved') {
            return 'Director Approved';
        }
        if ($this->hr_approved_at || $this->hr_status === 'Approved') {
            return 'Awaiting Director';
        }
        if ($this->supervisor_approved_at || $this->supervisor_status === 'Approved') {
            return 'Awaiting HR';
        }
        return 'Awaiting Supervisor';
    }

    // ── Workflow Checks ─────────────────────────────────────────────
    public function canSupervisorApprove(): bool
    {
        return $this->supervisor_status === 'Pending' || $this->supervisor_status === null;
    }

    public function canHrApprove(): bool
    {
        return ($this->supervisor_status === 'Approved') && ($this->hr_status === 'Pending' || $this->hr_status === null);
    }

    public function canDirectorApprove(): bool
    {
        return ($this->hr_status === 'Approved') && ($this->director_approved_at === null);
    }

    public function isRejected(): bool
    {
        return in_array('Rejected', [$this->supervisor_status, $this->hr_status, $this->approval_status]);
    }

    public function isFullyApproved(): bool
    {
        return $this->director_approved_at !== null || $this->approval_status === 'Approved';
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveRequest extends Model
{
    use SoftDeletes;

    // Overall workflow status derived from the three-stage chain
    const STATUS_PENDING   = 'Pending';
    const STATUS_APPROVED  = 'Approved';
    const STATUS_REJECTED  = 'Rejected';

    protected $fillable = [
        'employee_id',
        'leave_type',
        'start_date',
        'end_date',
        'supporting_document',
        'remarks',
        'duration_days',

        // Legacy single-approver (kept for backward compat)
        'approval_status',
        'approved_by',
        'approval_date',

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
    ];

    protected function casts(): array
    {
        return [
            'start_date'             => 'date',
            'end_date'               => 'date',
            'approval_date'          => 'date',
            'supervisor_approved_at' => 'datetime',
            'hr_approved_at'         => 'datetime',
            'director_approved_at'   => 'datetime',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approved_by');
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
        if (! $this->start_date || ! $this->end_date) {
            return 0;
        }
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    /**
     * Derive overall status from the three-stage chain.
     * Rejected at any stage = Rejected. All approved = Approved. Else = Pending.
     */
    public function getOverallStatusAttribute(): string
    {
        if ($this->supervisor_status === 'Rejected' || $this->hr_status === 'Rejected') {
            return self::STATUS_REJECTED;
        }
        if ($this->director_approved_at !== null) {
            return self::STATUS_APPROVED;
        }
        return self::STATUS_PENDING;
    }

    /**
     * Human-readable current stage label for the list view.
     */
    public function getCurrentStageAttribute(): string
    {
        if ($this->supervisor_status === 'Rejected' || $this->hr_status === 'Rejected') {
            return 'Rejected';
        }
        if ($this->director_approved_at) {
            return 'Director Approved';
        }
        if ($this->hr_approved_at) {
            return 'Awaiting Director';
        }
        if ($this->supervisor_approved_at) {
            return 'Awaiting HR';
        }
        return 'Awaiting Supervisor';
    }

    // ── Stage checks (used by action visibility) ───────────────────
    public function canSupervisorApprove(): bool
    {
        return $this->supervisor_status === 'Pending';
    }

    public function canHrApprove(): bool
    {
        return $this->supervisor_status === 'Approved' && $this->hr_status === 'Pending';
    }

    public function canDirectorApprove(): bool
    {
        return $this->hr_status === 'Approved' && $this->director_approved_at === null;
    }

    public function isRejected(): bool
    {
        return in_array('Rejected', [$this->supervisor_status, $this->hr_status]);
    }

    public function isFullyApproved(): bool
    {
        return $this->director_approved_at !== null;
    }
}

<?php

namespace App\Models\Procurement;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Requisition extends Model
{
    use SoftDeletes;

    protected $table = 'procurement_requisitions';

    // ── Status constants ────────────────────────────────────────────
    const STATUS_DRAFT       = 'Draft';
    const STATUS_SUBMITTED   = 'Submitted';
    const STATUS_APPROVED    = 'Approved';
    const STATUS_REJECTED    = 'Rejected';
    const STATUS_CONVERTED   = 'Converted to PO';
    const STAGE_PENDING      = 'Pending';
    const STAGE_APPROVED     = 'Approved';
    const STAGE_REJECTED     = 'Rejected';

    protected $fillable = [
        'requisition_number', 'budget_id', 'requested_by', 'department', 'cost_center',
        'budget_code', 'category', 'procurement_method', 'required_by_date',
        'justification', 'delivery_location', 'estimated_total', 'overall_status',

        // Stage 1 — Supervisor
        'supervisor_status', 'supervisor_approved_by', 'supervisor_approved_at', 'supervisor_remarks',
        // Stage 2 — Department Head
        'dept_head_status', 'dept_head_approved_by', 'dept_head_approved_at', 'dept_head_remarks',
        // Stage 3 — Finance
        'finance_status', 'finance_approved_by', 'finance_approved_at', 'finance_remarks',
        // Stage 4 — Procurement (Final)
        'procurement_status', 'procurement_approved_by', 'procurement_approved_at', 'procurement_remarks',
        'attachments',
    ];

    protected function casts(): array
    {
        return [
            'required_by_date'         => 'date',
            'estimated_total'          => 'decimal:2',
            'supervisor_approved_at'   => 'datetime',
            'dept_head_approved_at'    => 'datetime',
            'finance_approved_at'      => 'datetime',
            'procurement_approved_at'  => 'datetime',
            'attachments'              => 'array',
        ];
    }

    // ── Auto-generate requisition number ────────────────────────────
    protected static function booted(): void
    {
        static::creating(function (self $r) {
            if (empty($r->requisition_number)) {
                $year  = now()->format('Y');
                $month = now()->format('m');
                $next  = static::whereYear('created_at', $year)->count() + 1;
                $r->requisition_number = sprintf('REQ-%s%s-%04d', $year, $month, $next);
            }
            if (empty($r->overall_status)) {
                $r->overall_status = self::STATUS_DRAFT;
            }
        });
    }

    // ── Relationships ───────────────────────────────────────────────
    public function budget(): BelongsTo      { return $this->belongsTo(ProcurementBudget::class, 'budget_id'); }
    public function requester(): BelongsTo   { return $this->belongsTo(User::class, 'requested_by'); }

    public function supervisorApprover(): BelongsTo   { return $this->belongsTo(User::class, 'supervisor_approved_by'); }
    public function deptHeadApprover(): BelongsTo     { return $this->belongsTo(User::class, 'dept_head_approved_by'); }
    public function financeApprover(): BelongsTo      { return $this->belongsTo(User::class, 'finance_approved_by'); }
    public function procurementApprover(): BelongsTo  { return $this->belongsTo(User::class, 'procurement_approved_by'); }

    public function items(): HasMany         { return $this->hasMany(RequisitionItem::class); }
    public function purchaseOrder(): HasOne  { return $this->hasOne(PurchaseOrder::class); }
    public function tender(): HasOne         { return $this->hasOne(Tender::class); }

    // ── Stage predicates ────────────────────────────────────────────
    public function canSupervisorApprove(): bool
    {
        return $this->overall_status === self::STATUS_SUBMITTED
            && $this->supervisor_status === self::STAGE_PENDING;
    }

    public function canDeptHeadApprove(): bool
    {
        return $this->supervisor_status === self::STAGE_APPROVED
            && $this->dept_head_status === self::STAGE_PENDING;
    }

    public function canFinanceApprove(): bool
    {
        return $this->dept_head_status === self::STAGE_APPROVED
            && $this->finance_status === self::STAGE_PENDING;
    }

    public function canProcurementApprove(): bool
    {
        return $this->finance_status === self::STAGE_APPROVED
            && $this->procurement_status === self::STAGE_PENDING;
    }

    public function isRejected(): bool
    {
        return in_array('Rejected', [
            $this->supervisor_status, $this->dept_head_status,
            $this->finance_status, $this->procurement_status,
        ]);
    }

    public function isFullyApproved(): bool
    {
        return $this->overall_status === self::STATUS_APPROVED
            || $this->overall_status === self::STATUS_CONVERTED;
    }

    // ── Computed: current workflow stage label ─────────────────────
    public function getCurrentStageAttribute(): string
    {
        if ($this->isRejected()) return 'Rejected';
        if ($this->overall_status === self::STATUS_DRAFT) return 'Draft';
        if ($this->overall_status === self::STATUS_CONVERTED) return 'Converted to PO';
        if ($this->overall_status === self::STATUS_APPROVED) return 'Fully Approved';

        if ($this->supervisor_status === self::STAGE_PENDING) return 'Awaiting Supervisor';
        if ($this->dept_head_status === self::STAGE_PENDING)  return 'Awaiting Dept Head';
        if ($this->finance_status === self::STAGE_PENDING)    return 'Awaiting Finance';
        if ($this->procurement_status === self::STAGE_PENDING) return 'Awaiting Procurement';

        return 'Under Review';
    }
}

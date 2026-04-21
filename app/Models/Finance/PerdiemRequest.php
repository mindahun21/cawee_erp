<?php

namespace App\Models\Finance;

use App\Models\Currency;
use App\Models\Donor;
use App\Models\Employee;
use App\Models\Project;
use App\Models\User;
use App\Traits\Finance\HasFinanceAuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class PerdiemRequest extends Model
{
    use SoftDeletes, HasFinanceAuditLog;

    protected $table = 'finance_perdiem_requests';

    protected $fillable = [
        'reference', 'employee_id', 'perdiem_type_id',
        'travel_destination', 'purpose',
        'start_date', 'end_date', 'days_count',
        'daily_rate', 'total_requested', 'currency_id',
        'activity_code', 'project_id', 'cost_center_id', 'donor_id',
        'advance_requested', 'amount_advanced',
        'status', 'approval_stage',
        'prepared_by', 'approved_by', 'approved_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date'        => 'date',
            'end_date'          => 'date',
            'approved_at'       => 'datetime',
            'daily_rate'        => 'decimal:2',
            'total_requested'   => 'decimal:2',
            'amount_advanced'   => 'decimal:2',
            'advance_requested' => 'boolean',
        ];
    }

    // ── Status helpers ──────────────────────────────────────────────

    public static function statuses(): array
    {
        return [
            'draft'     => 'Draft',
            'pending'   => 'Pending Approval',
            'approved'  => 'Approved',
            'rejected'  => 'Rejected',
            'settled'   => 'Settled',
            'cancelled' => 'Cancelled',
        ];
    }

    public function isDraft(): bool    { return $this->status === 'draft'; }
    public function isPending(): bool  { return $this->status === 'pending'; }
    public function isApproved(): bool { return $this->status === 'approved'; }
    public function isSettled(): bool  { return $this->status === 'settled'; }

    // ── Computed ────────────────────────────────────────────────────

    /** Remaining balance after advance (negative = employee owes back) */
    public function settlementBalance(): float
    {
        return (float) $this->total_requested - (float) $this->amount_advanced;
    }

    // ── Relationships ───────────────────────────────────────────────

    public function employee(): BelongsTo    { return $this->belongsTo(Employee::class); }
    public function perdiemType(): BelongsTo { return $this->belongsTo(PerdiemType::class, 'perdiem_type_id'); }
    public function currency(): BelongsTo    { return $this->belongsTo(Currency::class); }
    public function costCenter(): BelongsTo  { return $this->belongsTo(CostCenter::class); }
    public function project(): BelongsTo     { return $this->belongsTo(Project::class); }
    public function donor(): BelongsTo       { return $this->belongsTo(Donor::class); }
    public function preparedBy(): BelongsTo  { return $this->belongsTo(User::class, 'prepared_by'); }
    public function approvedBy(): BelongsTo  { return $this->belongsTo(User::class, 'approved_by'); }

    public function extensions(): HasMany    { return $this->hasMany(PerdiemRequestExtension::class, 'perdiem_request_id'); }
    public function settlement(): HasOne     { return $this->hasOne(PerdiemSettlement::class, 'perdiem_request_id'); }
}

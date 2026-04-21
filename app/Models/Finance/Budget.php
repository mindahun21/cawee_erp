<?php

namespace App\Models\Finance;

use App\Models\Currency;
use App\Models\Donor;
use App\Models\Project;
use App\Models\User;
use App\Traits\Finance\HasFinanceAuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Budget extends Model
{
    use SoftDeletes, HasFinanceAuditLog;

    protected $table = 'finance_budgets';

    protected $fillable = [
        'budget_code', 'name', 'budget_type_id', 'donor_id', 'project_id',
        'cost_center_id', 'currency_id', 'fiscal_year',
        'total_budget_amount', 'committed_amount', 'encumbered_amount', 'actual_spent',
        'status', 'approved_by', 'approved_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'total_budget_amount' => 'decimal:2',
            'committed_amount'    => 'decimal:2',
            'encumbered_amount'   => 'decimal:2',
            'actual_spent'        => 'decimal:2',
            'approved_at'         => 'datetime',
        ];
    }

    public static function statuses(): array
    {
        return ['draft' => 'Draft', 'approved' => 'Approved', 'active' => 'Active', 'closed' => 'Closed', 'cancelled' => 'Cancelled'];
    }

    public function isDraft(): bool    { return $this->status === 'draft'; }
    public function isActive(): bool   { return $this->status === 'active'; }
    public function isApproved(): bool { return $this->status === 'approved'; }

    /** Remaining = Total - Committed - Encumbered - Actual */
    public function remaining(): float
    {
        return (float)$this->total_budget_amount
            - (float)$this->committed_amount
            - (float)$this->encumbered_amount
            - (float)$this->actual_spent;
    }

    /** % utilization = (committed + encumbered + actual) / total */
    public function utilizationPct(): float
    {
        $total = (float)$this->total_budget_amount;
        return $total > 0
            ? round(((float)$this->committed_amount + (float)$this->encumbered_amount + (float)$this->actual_spent) / $total * 100, 1)
            : 0;
    }

    // ── Relationships ───────────────────────────────────────────────
    public function budgetType(): BelongsTo  { return $this->belongsTo(BudgetType::class); }
    public function donor(): BelongsTo       { return $this->belongsTo(Donor::class); }
    public function project(): BelongsTo     { return $this->belongsTo(Project::class); }
    public function costCenter(): BelongsTo  { return $this->belongsTo(CostCenter::class); }
    public function currency(): BelongsTo    { return $this->belongsTo(Currency::class); }
    public function approvedBy(): BelongsTo  { return $this->belongsTo(User::class, 'approved_by'); }

    public function lines(): HasMany      { return $this->hasMany(BudgetLine::class); }
    public function revisions(): HasMany  { return $this->hasMany(BudgetRevision::class); }
    public function commitments(): HasMany { return $this->hasMany(Commitment::class); }
    public function encumbrances(): HasMany { return $this->hasMany(Encumbrance::class); }
}

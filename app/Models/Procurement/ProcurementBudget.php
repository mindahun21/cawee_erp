<?php

namespace App\Models\Procurement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcurementBudget extends Model
{
    use SoftDeletes;

    protected $table = 'procurement_budgets';

    protected $fillable = [
        'code', 'title', 'department', 'cost_center', 'fiscal_year',
        'allocated_amount', 'committed_amount', 'expended_amount', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'allocated_amount' => 'decimal:2',
            'committed_amount' => 'decimal:2',
            'expended_amount'  => 'decimal:2',
        ];
    }

    // ── Relationships ───────────────────────────────────────────────
    public function requisitions(): HasMany { return $this->hasMany(Requisition::class, 'budget_id'); }

    // ── Computed ────────────────────────────────────────────────────
    public function getAvailableAmountAttribute(): float
    {
        return max(0, (float) $this->allocated_amount - (float) $this->committed_amount - (float) $this->expended_amount);
    }

    public function getUtilizationPercentageAttribute(): float
    {
        if ($this->allocated_amount == 0) return 0;
        return round(((float)$this->expended_amount / (float)$this->allocated_amount) * 100, 1);
    }

    public function hasSufficientBudget(float $amount): bool
    {
        return $this->available_amount >= $amount;
    }
}

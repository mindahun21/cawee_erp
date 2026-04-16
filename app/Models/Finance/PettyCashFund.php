<?php

namespace App\Models\Finance;

use App\Models\User;
use App\Traits\Finance\HasFinanceAuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PettyCashFund extends Model
{
    use SoftDeletes, HasFinanceAuditLog;

    protected $table = 'finance_petty_cash_funds';

    protected $fillable = [
        'fund_name',
        'fund_code',
        'cashier_id',
        'cost_center_id',
        'currency_id',
        'chart_of_account_id',
        'opening_balance',
        'current_balance',
        'max_limit',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
            'current_balance' => 'decimal:2',
            'max_limit'       => 'decimal:2',
        ];
    }

    // ── Static helpers ─────────────────────────────────────────────────

    public static function statuses(): array
    {
        return [
            'active'    => 'Active',
            'suspended' => 'Suspended',
            'closed'    => 'Closed',
        ];
    }

    public static function activeOptions(): array
    {
        return static::where('status', 'active')
            ->orderBy('fund_name')
            ->get()
            ->mapWithKeys(fn ($f) => [$f->id => "[{$f->fund_code}] {$f->fund_name}"])
            ->toArray();
    }

    // ── Status helpers ─────────────────────────────────────────────────

    public function isActive(): bool    { return $this->status === 'active'; }
    public function isSuspended(): bool { return $this->status === 'suspended'; }
    public function isClosed(): bool    { return $this->status === 'closed'; }

    /**
     * Whether the fund needs replenishment (balance < 20% of max_limit).
     */
    public function needsReplenishment(): bool
    {
        return (float) $this->current_balance < ((float) $this->max_limit * 0.20);
    }

    /**
     * Utilization ratio as a percentage.
     */
    public function utilizationPercent(): float
    {
        $max = (float) $this->max_limit;
        if ($max <= 0) return 0;
        return round((($max - (float) $this->current_balance) / $max) * 100, 1);
    }

    // ── Relationships ─────────────────────────────────────────────────

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(Cashier::class, 'cashier_id');
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class, 'cost_center_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Currency::class);
    }

    public function chartOfAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PettyCashPayment::class, 'petty_cash_fund_id');
    }

    public function replenishments(): HasMany
    {
        return $this->hasMany(PettyCashReplenishment::class, 'petty_cash_fund_id');
    }
}

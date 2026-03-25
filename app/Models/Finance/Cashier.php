<?php

namespace App\Models\Finance;

use App\Models\Currency;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cashier extends Model
{
    protected $table = 'finance_cashiers';

    protected $fillable = [
        'employee_id',
        'cost_center_id',
        'currency_id',
        'fund_limit',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'fund_limit' => 'decimal:2',
            'is_active'  => 'boolean',
        ];
    }

    // ── Helpers ───────────────────────────────────────────────────────

    public static function activeOptions(): array
    {
        return static::with('employee')
            ->where('is_active', true)
            ->get()
            ->mapWithKeys(fn ($c) => [$c->id => $c->employee?->full_name ?? "Cashier #{$c->id}"])
            ->toArray();
    }

    // ── Relationships ─────────────────────────────────────────────────

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function pettyCashFunds(): HasMany
    {
        return $this->hasMany(PettyCashFund::class, 'cashier_id');
    }
}

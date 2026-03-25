<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetType extends Model
{
    protected $table = 'finance_budget_types';

    protected $fillable = [
        'code',
        'name',
        'category',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // ── Helpers ───────────────────────────────────────────────────────

    public static function categories(): array
    {
        return [
            'operational'  => 'Operational',
            'project'      => 'Project',
            'donor_funded' => 'Donor-Funded',
            'capital'      => 'Capital',
            'emergency'    => 'Emergency',
        ];
    }

    public static function activeOptions(): array
    {
        return static::where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    // ── Relationships ─────────────────────────────────────────────────

    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class, 'budget_type_id');
    }
}

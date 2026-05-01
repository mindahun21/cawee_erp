<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountSubClassification extends Model
{
    protected $table = 'finance_account_sub_classifications';

    protected $fillable = [
        'name',
        'code',
        'classification',
        'description',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active'  => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    // ── Helpers ───────────────────────────────────────────────────────

    /**
     * The top-level classification labels (same as AccountType::classifications()).
     */
    public static function classificationLabels(): array
    {
        return [
            'asset'     => 'Asset',
            'liability' => 'Liability',
            'equity'    => 'Equity',
            'income'    => 'Income',
            'expense'   => 'Expense',
        ];
    }

    /**
     * Active options grouped for use in Select dropdowns.
     * Returns ['Asset' => [id => name, ...], 'Liability' => [...], ...]
     */
    public static function groupedOptions(): array
    {
        return static::where('is_active', true)
            ->orderBy('classification')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->groupBy(fn ($m) => static::classificationLabels()[$m->classification] ?? $m->classification)
            ->map(fn ($group) => $group->pluck('name', 'id'))
            ->toArray();
    }

    /**
     * Flat options filtered by classification (for reactive selects on CoA form).
     */
    public static function optionsForClassification(string $classification): array
    {
        return static::where('is_active', true)
            ->where('classification', $classification)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    // ── Relationships ─────────────────────────────────────────────────

    public function chartOfAccounts(): HasMany
    {
        return $this->hasMany(ChartOfAccount::class, 'sub_classification_id');
    }
}

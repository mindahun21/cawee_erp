<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinancialStatementCategory extends Model
{
    protected $table = 'finance_financial_statement_categories';

    protected $fillable = [
        'code',
        'name',
        'statement_type',
        'display_order',
        'parent_id',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
            'is_active'     => 'boolean',
        ];
    }

    // ── Static helpers ────────────────────────────────────────────────

    public static function statementTypes(): array
    {
        return [
            'balance_sheet'    => 'Balance Sheet',
            'income_statement' => 'Income Statement',
            'cash_flow'        => 'Cash Flow Statement',
        ];
    }

    /**
     * Returns [id => "[CODE] Name"] map for active categories,
     * optionally filtered by statement_type.
     */
    public static function activeOptions(?string $statementType = null): array
    {
        $query = static::where('is_active', true)
            ->orderBy('statement_type')
            ->orderBy('display_order')
            ->orderBy('name');

        if ($statementType) {
            $query->where('statement_type', $statementType);
        }

        return $query->get()
            ->mapWithKeys(fn ($c) => [$c->id => "[{$c->code}] {$c->name}"])
            ->toArray();
    }

    /**
     * Returns options grouped by statement type for Select::make()->grouped().
     */
    public static function groupedOptions(): array
    {
        $groups = [];

        foreach (static::statementTypes() as $type => $label) {
            $items = static::where('is_active', true)
                ->where('statement_type', $type)
                ->orderBy('display_order')
                ->get()
                ->mapWithKeys(fn ($c) => [$c->id => "[{$c->code}] {$c->name}"])
                ->toArray();

            if (!empty($items)) {
                $groups[$label] = $items;
            }
        }

        return $groups;
    }

    // ── Relationships ─────────────────────────────────────────────────

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function chartOfAccounts(): HasMany
    {
        return $this->hasMany(ChartOfAccount::class, 'financial_statement_category_id');
    }
}

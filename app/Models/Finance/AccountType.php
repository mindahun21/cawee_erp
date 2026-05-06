<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountType extends Model
{
    protected $table = 'finance_account_types';

    protected $fillable = [
        'code',
        'name',
        'classification',
        'normal_balance',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // ── Classification helpers ────────────────────────────────────────

    public static function classifications(): array
    {
        return [
            'asset'     => 'Asset',
            'liability' => 'Liability',
            'equity'    => 'Equity',
            'income'    => 'Income',
            'expense'   => 'Expense',
            // Note: 'bank' classification is stored as 'asset' in the DB so that
            // all report WHERE-IN clauses continue to work without changes.
            // The dedicated 'Bank' AccountType row uses classification='asset'
            // and is shown to users under the 'Bank' name via the name column.
        ];
    }

    public static function normalBalances(): array
    {
        return [
            'debit'  => 'Debit',
            'credit' => 'Credit',
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

    /**
     * Chart of Accounts entries that belong to this type.
     * Defined in Phase 2; declared here for forward-reference.
     */
    public function chartOfAccounts(): HasMany
    {
        return $this->hasMany(ChartOfAccount::class, 'account_type_id');
    }
}

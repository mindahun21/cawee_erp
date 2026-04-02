<?php

namespace App\Models\Finance;

use App\Traits\Finance\HasFinanceAuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChartOfAccount extends Model
{
    use SoftDeletes, HasFinanceAuditLog;

    protected $table = 'finance_chart_of_accounts';

    protected $fillable = [
        'code',
        'name',
        'account_type_id',
        'parent_id',
        'financial_statement_category_id',
        'currency_id',
        'is_active',
        'is_control_account',
        'is_donor_fund_account',
        'level',
        'is_header',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_active'             => 'boolean',
            'is_donor_fund_account' => 'boolean',
            'is_header'             => 'boolean',
            'level'                 => 'integer',
        ];
    }

    // ── Static helpers ────────────────────────────────────────────────

    public static function controlAccountOptions(): array
    {
        return [
            'none' => 'None',
            'ap'   => 'Accounts Payable (AP)',
            'ar'   => 'Accounts Receivable (AR)',
            'bank' => 'Bank',
        ];
    }

    /**
     * Returns only leaf (non-header), active accounts for use in
     * journal entry line dropdowns.
     */
    public static function transactionOptions(): array
    {
        return static::where('is_active', true)
            ->where('is_header', false)
            ->orderBy('code')
            ->get()
            ->mapWithKeys(fn ($a) => [$a->id => "[{$a->code}] {$a->name}"])
            ->toArray();
    }

    /**
     * Returns all active accounts (headers + leaves) for the parent
     * account selector in the CoA form.
     */
    public static function hierarchyOptions(): array
    {
        return static::where('is_active', true)
            ->orderBy('code')
            ->get()
            ->mapWithKeys(fn ($a) => [$a->id => "[{$a->code}] {$a->name}"])
            ->toArray();
    }

    // ── Balance helpers ────────────────────────────────────────────────

    /**
     * Returns the most recent running balance from the General Ledger
     * for this account. Returns 0.00 if no GL entries exist yet.
     */
    public function currentBalance(): float
    {
        $last = GeneralLedger::where('account_id', $this->id)
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->first();

        return $last ? (float) $last->running_balance : 0.0;
    }

    /**
     * Returns the running balance as of a specific date.
     */
    public function balanceAsOf(\DateTimeInterface|string $date): float
    {
        $last = GeneralLedger::where('account_id', $this->id)
            ->where('transaction_date', '<=', $date)
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->first();

        return $last ? (float) $last->running_balance : 0.0;
    }

    /**
     * Sum of debit postings for this account within a date range.
     */
    public function totalDebits(string $from, string $to): float
    {
        return (float) GeneralLedger::where('account_id', $this->id)
            ->whereBetween('transaction_date', [$from, $to])
            ->sum('debit');
    }

    /**
     * Sum of credit postings for this account within a date range.
     */
    public function totalCredits(string $from, string $to): float
    {
        return (float) GeneralLedger::where('account_id', $this->id)
            ->whereBetween('transaction_date', [$from, $to])
            ->sum('credit');
    }

    // ── Relationships ─────────────────────────────────────────────────

    public function accountType(): BelongsTo
    {
        return $this->belongsTo(AccountType::class, 'account_type_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Recursively load all descendants (children, grandchildren, etc.).
     */
    public function allChildren(): HasMany
    {
        return $this->children()->with('allChildren');
    }

    public function financialStatementCategory(): BelongsTo
    {
        return $this->belongsTo(FinancialStatementCategory::class, 'financial_statement_category_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Currency::class, 'currency_id');
    }

    public function journalEntryLines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class, 'account_id');
    }

    public function generalLedger(): HasMany
    {
        return $this->hasMany(GeneralLedger::class, 'account_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(FinanceAuditLog::class, 'auditable_id')
            ->where('auditable_type', static::class)
            ->orderByDesc('created_at');
    }
}

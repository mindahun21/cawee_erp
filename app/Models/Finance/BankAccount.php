<?php

namespace App\Models\Finance;

use App\Models\Currency;
use App\Models\Donor;
use App\Traits\Finance\HasFinanceAuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankAccount extends Model
{
    use SoftDeletes, HasFinanceAuditLog;

    protected $table = 'finance_bank_accounts';

    protected $fillable = [
        'account_name',
        'bank_name',
        'account_number',
        'branch',
        'swift_code',
        'account_type',
        'currency_id',
        'chart_of_account_id',
        'cost_center_id',
        'donor_id',
        'balance_as_of_date',
        'opening_balance',
        'current_balance',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'balance_as_of_date' => 'date',
            'opening_balance'    => 'decimal:2',
            'current_balance'    => 'decimal:2',
            'is_active'          => 'boolean',
        ];
    }

    // ── Static helpers ─────────────────────────────────────────────────

    public static function accountTypes(): array
    {
        return [
            'current'          => 'Current Account',
            'savings'          => 'Savings Account',
            'project_specific' => 'Project-Specific Account',
        ];
    }

    public static function activeOptions(): array
    {
        return static::where('is_active', true)
            ->orderBy('account_name')
            ->get()
            ->mapWithKeys(fn ($b) => [$b->id => "{$b->bank_name} — {$b->account_name} ({$b->account_number})"])
            ->toArray();
    }

    // ── Status helpers ─────────────────────────────────────────────────

    public function isActive(): bool { return $this->is_active; }

    // ── Relationships ──────────────────────────────────────────────────

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function chartOfAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_id');
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class, 'cost_center_id');
    }

    public function donor(): BelongsTo
    {
        return $this->belongsTo(Donor::class, 'donor_id');
    }

    public function cashReceiptVouchers(): HasMany
    {
        return $this->hasMany(CashReceiptVoucher::class, 'bank_account_id');
    }

    public function paymentVouchers(): HasMany
    {
        return $this->hasMany(PaymentVoucher::class, 'bank_account_id');
    }

    public function reconciliations(): HasMany
    {
        return $this->hasMany(BankReconciliation::class, 'bank_account_id');
    }

    public function outboundTransfers(): HasMany
    {
        return $this->hasMany(FundTransfer::class, 'from_bank_account_id');
    }

    public function inboundTransfers(): HasMany
    {
        return $this->hasMany(FundTransfer::class, 'to_bank_account_id');
    }
}

<?php

namespace App\Models\Finance;

use App\Models\Currency;
use App\Models\Donor;
use App\Models\Project;
use App\Models\User;
use App\Traits\Finance\HasFinanceAuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FundTransfer extends Model
{
    use SoftDeletes, HasFinanceAuditLog;

    protected $table = 'finance_fund_transfers';

    protected $fillable = [
        'transfer_number',
        'accounting_period_id',
        'transfer_date',
        'from_bank_account_id',
        'to_bank_account_id',
        'from_cost_center_id',
        'to_cost_center_id',
        'amount',
        'currency_id',
        'exchange_rate_to_base',
        'project_id',
        'donor_id',
        'purpose',
        'journal_entry_id',
        'status',
        'prepared_by',
        'approved_by',
        'approved_at',
        'confirmed_by',
        'confirmed_at',
        'confirmation_reference',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'transfer_date'         => 'date',
            'amount'                => 'decimal:2',
            'exchange_rate_to_base' => 'decimal:6',
            'approved_at'           => 'datetime',
            'confirmed_at'          => 'datetime',
        ];
    }

    // ── Static helpers ─────────────────────────────────────────────────

    public static function statuses(): array
    {
        return [
            'draft'      => 'Draft',
            'approved'   => 'Approved',
            'remitted'   => 'Remitted (Sent)',
            'confirmed'  => 'Confirmed (Received)',
            'reconciled' => 'Reconciled',
        ];
    }

    // ── Status predicates ─────────────────────────────────────────────

    public function isDraft(): bool      { return $this->status === 'draft'; }
    public function isApproved(): bool   { return $this->status === 'approved'; }
    public function isRemitted(): bool   { return $this->status === 'remitted'; }
    public function isConfirmed(): bool  { return $this->status === 'confirmed'; }
    public function isReconciled(): bool { return $this->status === 'reconciled'; }

    // ── Relationships ─────────────────────────────────────────────────

    public function period(): BelongsTo
    {
        return $this->belongsTo(AccountingPeriod::class, 'accounting_period_id');
    }

    public function fromBankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'from_bank_account_id');
    }

    public function toBankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'to_bank_account_id');
    }

    public function fromCostCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class, 'from_cost_center_id');
    }

    public function toCostCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class, 'to_cost_center_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function donor(): BelongsTo
    {
        return $this->belongsTo(Donor::class, 'donor_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}

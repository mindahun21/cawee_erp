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

class IncomeRegister extends Model
{
    use SoftDeletes, HasFinanceAuditLog;

    protected $table = 'finance_income_registers';

    protected $fillable = [
        'reference', 'income_date', 'source_name',
        'donor_id', 'income_type',
        'amount', 'currency_id', 'exchange_rate_to_base', 'amount_in_base',
        'project_id', 'cost_center_id', 'bank_account_id',
        'receipt_reference', 'description', 'document_attachments',
        'status', 'journal_entry_id',
        'prepared_by', 'confirmed_by', 'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'income_date'          => 'date',
            'amount'               => 'decimal:2',
            'amount_in_base'       => 'decimal:2',
            'document_attachments' => 'array',
            'confirmed_at'         => 'datetime',
        ];
    }

    public static function incomeTypes(): array
    {
        return ['grant' => 'Grant', 'service_fee' => 'Service Fee', 'interest' => 'Interest', 'other' => 'Other'];
    }

    public static function statuses(): array
    {
        return ['draft' => 'Draft', 'confirmed' => 'Confirmed', 'posted' => 'Posted'];
    }

    public function isDraft(): bool      { return $this->status === 'draft'; }
    public function isConfirmed(): bool  { return $this->status === 'confirmed'; }
    public function isPosted(): bool     { return $this->status === 'posted'; }

    public function currency(): BelongsTo     { return $this->belongsTo(Currency::class, 'currency_id'); }
    public function costCenter(): BelongsTo   { return $this->belongsTo(CostCenter::class, 'cost_center_id'); }
    public function bankAccount(): BelongsTo  { return $this->belongsTo(BankAccount::class, 'bank_account_id'); }
    public function donor(): BelongsTo        { return $this->belongsTo(Donor::class, 'donor_id'); }
    public function project(): BelongsTo      { return $this->belongsTo(Project::class, 'project_id'); }
    public function journalEntry(): BelongsTo { return $this->belongsTo(JournalEntry::class, 'journal_entry_id'); }
    public function preparedBy(): BelongsTo   { return $this->belongsTo(User::class, 'prepared_by'); }
    public function confirmedBy(): BelongsTo  { return $this->belongsTo(User::class, 'confirmed_by'); }
}

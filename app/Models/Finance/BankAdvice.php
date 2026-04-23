<?php

namespace App\Models\Finance;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankAdvice extends Model
{
    protected $table = 'finance_bank_advices';

    protected $fillable = [
        'reference_number', 'advice_date', 'bank_account_id', 'advice_type',
        'amount', 'currency_id', 'description', 'status', 'journal_entry_id'
    ];

    protected function casts(): array
    {
        return [
            'advice_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function bankAccount(): BelongsTo { return $this->belongsTo(BankAccount::class); }
    public function currency(): BelongsTo { return $this->belongsTo(Currency::class); }
    public function journalEntry(): BelongsTo { return $this->belongsTo(JournalEntry::class, 'journal_entry_id'); }
}

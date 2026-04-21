<?php

namespace App\Models\Finance;

use App\Models\Currency;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankDepositSlip extends Model
{
    protected $table = 'finance_bank_deposit_slips';

    protected $fillable = [
        'slip_number', 'deposit_date', 'bank_account_id', 'total_amount',
        'currency_id', 'notes', 'status', 'prepared_by'
    ];

    protected function casts(): array
    {
        return [
            'deposit_date' => 'date',
            'total_amount' => 'decimal:2',
        ];
    }

    public function bankAccount(): BelongsTo { return $this->belongsTo(BankAccount::class); }
    public function currency(): BelongsTo { return $this->belongsTo(Currency::class); }
    public function preparedBy(): BelongsTo { return $this->belongsTo(User::class, 'prepared_by'); }
}

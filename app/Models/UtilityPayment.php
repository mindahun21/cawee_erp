<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UtilityPayment extends Model
{
    protected $table = 'hr_utility_payments';

    protected $fillable = [
        'branch_utility_id',
        'period_start',
        'period_end',
        'due_date',
        'amount',
        'status',
        'paid_at',
        'payment_reference',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'due_date' => 'date',
            'paid_at' => 'datetime',
            'amount' => 'decimal:2',
        ];
    }

    public function utility(): BelongsTo
    {
        return $this->belongsTo(BranchUtility::class, 'branch_utility_id');
    }
}


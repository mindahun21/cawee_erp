<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanRepaymentSchedule extends Model
{
    protected $table = 'finance_loan_repayment_schedules';

    protected $fillable = [
        'loan_id', 'installment_number', 'due_date',
        'principal_amount', 'interest_amount', 'total_due',
        'paid_amount', 'paid_date', 'journal_entry_id', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'due_date'         => 'date',
            'paid_date'        => 'date',
            'principal_amount' => 'decimal:2',
            'interest_amount'  => 'decimal:2',
            'total_due'        => 'decimal:2',
            'paid_amount'      => 'decimal:2',
        ];
    }

    public static function statuses(): array
    {
        return [
            'pending'       => 'Pending',
            'partially_paid'=> 'Partially Paid',
            'paid'          => 'Paid',
            'overdue'       => 'Overdue',
        ];
    }

    public function isPaid(): bool    { return $this->status === 'paid'; }
    public function isOverdue(): bool { return $this->status === 'overdue' || ($this->status === 'pending' && $this->due_date < today()); }

    public function loan(): BelongsTo         { return $this->belongsTo(Loan::class, 'loan_id'); }
    public function journalEntry(): BelongsTo { return $this->belongsTo(JournalEntry::class, 'journal_entry_id'); }

    public function balanceDue(): float
    {
        return (float) $this->total_due - (float) $this->paid_amount;
    }
}

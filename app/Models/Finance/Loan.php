<?php

namespace App\Models\Finance;

use App\Models\Currency;
use App\Models\User;
use App\Traits\Finance\HasFinanceAuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Loan extends Model
{
    use SoftDeletes, HasFinanceAuditLog;

    protected $table = 'finance_loans';

    protected $fillable = [
        'loan_reference', 'borrower_type', 'borrower_id', 'loan_purpose',
        'principal_amount', 'interest_rate', 'tenor_months',
        'disbursement_date', 'start_repayment_date', 'maturity_date',
        'outstanding_balance', 'total_interest',
        'bank_account_id', 'currency_id', 'journal_entry_id',
        'status', 'prepared_by', 'approved_by', 'approved_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'principal_amount'    => 'decimal:2',
            'interest_rate'       => 'decimal:4',
            'outstanding_balance' => 'decimal:2',
            'total_interest'      => 'decimal:2',
            'disbursement_date'   => 'date',
            'start_repayment_date'=> 'date',
            'maturity_date'       => 'date',
            'approved_at'         => 'datetime',
        ];
    }

    public static function statuses(): array
    {
        return ['active' => 'Active', 'fully_paid' => 'Fully Paid', 'written_off' => 'Written Off'];
    }

    public static function borrowerTypes(): array
    {
        return ['employee' => 'Employee', 'organization' => 'Organization'];
    }

    public function isActive(): bool    { return $this->status === 'active'; }
    public function isFullyPaid(): bool { return $this->status === 'fully_paid'; }

    // ── Relationships ──────────────────────────────────────────────────

    public function schedule(): HasMany
    {
        return $this->hasMany(LoanRepaymentSchedule::class, 'loan_id')->orderBy('installment_number');
    }

    public function currency(): BelongsTo     { return $this->belongsTo(Currency::class, 'currency_id'); }
    public function bankAccount(): BelongsTo  { return $this->belongsTo(BankAccount::class, 'bank_account_id'); }
    public function preparedBy(): BelongsTo   { return $this->belongsTo(User::class, 'prepared_by'); }
    public function approvedBy(): BelongsTo   { return $this->belongsTo(User::class, 'approved_by'); }
    public function journalEntry(): BelongsTo { return $this->belongsTo(JournalEntry::class, 'journal_entry_id'); }

    // ── Computed helpers ───────────────────────────────────────────────

    public function nextInstallment(): ?LoanRepaymentSchedule
    {
        return $this->schedule()->where('status', 'pending')->orderBy('due_date')->first();
    }

    /** Generate a full amortisation schedule (flat/equal instalments) */
    public function generateSchedule(): void
    {
        $principal     = (float) $this->principal_amount;
        $monthlyRate   = (float) $this->interest_rate / 12;
        $tenor         = (int) $this->tenor_months;
        $startDate     = $this->start_repayment_date;

        // Equal principal instalments
        $principalPerMonth = round($principal / $tenor, 2);

        $schedules = [];
        $balance   = $principal;

        for ($i = 1; $i <= $tenor; $i++) {
            $interest    = round($balance * $monthlyRate, 2);
            $principalPmt = ($i === $tenor) ? $balance : $principalPerMonth;
            $balance     -= $principalPmt;

            $schedules[] = [
                'loan_id'             => $this->id,
                'installment_number'  => $i,
                'due_date'            => $startDate->copy()->addMonths($i - 1),
                'principal_amount'    => $principalPmt,
                'interest_amount'     => $interest,
                'total_due'           => $principalPmt + $interest,
                'paid_amount'         => 0,
                'status'              => 'pending',
                'created_at'          => now(),
                'updated_at'          => now(),
            ];
        }

        LoanRepaymentSchedule::insert($schedules);
    }
}

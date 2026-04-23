<?php

namespace App\Models\Finance;

use App\Models\Employee;
use App\Models\User;
use App\Traits\Finance\HasFinanceAuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollSummary extends Model
{
    use SoftDeletes, HasFinanceAuditLog;

    protected $table = 'finance_payroll_summaries';

    protected $fillable = [
        'payroll_month', 'payroll_year',
        'employee_id', 'payroll_id', 'department_id',
        'cost_center_id', 'project_id', 'donor_id', 'currency_id',
        'basic_salary', 'allowances_total', 'gross_pay',
        'income_tax_withheld', 'pension_employee', 'pension_employer',
        'other_deductions', 'deductions_total', 'net_pay', 'employer_total_cost',
        'status', 'journal_entry_id', 'prepared_by', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'basic_salary'        => 'decimal:2',
            'allowances_total'    => 'decimal:2',
            'gross_pay'           => 'decimal:2',
            'income_tax_withheld' => 'decimal:2',
            'pension_employee'    => 'decimal:2',
            'pension_employer'    => 'decimal:2',
            'other_deductions'    => 'decimal:2',
            'deductions_total'    => 'decimal:2',
            'net_pay'             => 'decimal:2',
            'employer_total_cost' => 'decimal:2',
        ];
    }

    // ── Status helpers ──────────────────────────────────────────────

    public static function statuses(): array
    {
        return ['draft' => 'Draft', 'journal_posted' => 'Journal Posted'];
    }

    public function isDraft(): bool        { return $this->status === 'draft'; }
    public function isPosted(): bool       { return $this->status === 'journal_posted'; }

    // ── Month label helper ──────────────────────────────────────────

    public function monthLabel(): string
    {
        return \Carbon\Carbon::createFromDate($this->payroll_year, $this->payroll_month, 1)
            ->format('F Y');
    }

    // ── Ethiopian income tax (progressive brackets 2024) ─────────────

    /**
     * Calculate Ethiopian PAYE Income Tax based on monthly gross.
     * Source: Ethiopian Income Tax Proclamation No. 979/2016 Annex I.
     */
    public static function computeIncomeTax(float $grossMonthly): float
    {
        return match (true) {
            $grossMonthly <= 600   => 0,
            $grossMonthly <= 1650  => ($grossMonthly - 600)  * 0.10,
            $grossMonthly <= 3200  => ($grossMonthly - 1650) * 0.15 + 105,
            $grossMonthly <= 5250  => ($grossMonthly - 3200) * 0.20 + 337.50,
            $grossMonthly <= 7800  => ($grossMonthly - 5250) * 0.25 + 747.50,
            $grossMonthly <= 10900 => ($grossMonthly - 7800) * 0.30 + 1385,
            default                 => ($grossMonthly - 10900) * 0.35 + 2315,
        };
    }

    // ── Relationships ───────────────────────────────────────────────

    public function employee(): BelongsTo     { return $this->belongsTo(Employee::class); }
    public function payroll(): BelongsTo      { return $this->belongsTo(\App\Models\Payroll::class); }
    public function costCenter(): BelongsTo   { return $this->belongsTo(CostCenter::class); }
    public function journalEntry(): BelongsTo { return $this->belongsTo(JournalEntry::class); }
    public function preparedBy(): BelongsTo   { return $this->belongsTo(User::class, 'prepared_by'); }
    public function currency(): BelongsTo     { return $this->belongsTo(\App\Models\Currency::class); }
}

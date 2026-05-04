<?php

namespace App\Services\Finance;

use App\Models\Employee;
use App\Models\Finance\AccountingPeriod;
use App\Models\Finance\ChartOfAccount;
use App\Models\Finance\FinanceSetting;
use App\Models\Finance\JournalEntry;
use App\Models\Finance\JournalEntryLine;
use App\Models\Finance\PayrollSummary;
use App\Models\Finance\PerdiemRequest;
use App\Models\Finance\PerdiemSettlement;
use App\Models\Payroll;
use Illuminate\Support\Facades\DB;

/**
 * PayrollGLPostingService
 *
 * Handles:
 *   1. Building a PayrollSummary from an HR Payroll record
 *   2. Posting the PayrollSummary to the GL (double-entry):
 *        DR: Salary Expense (basic)
 *        DR: Allowance Expense
 *        DR: Pension Expense (employer)
 *        CR: Net Salary Payable
 *        CR: Employee Pension Payable
 *        CR: Income Tax Payable
 *   3. Per-Diem settlement GL posting:
 *        If employee owes back: DR: Advance Receivable, CR: Per Diem Expense
 *        If claimable remains:  DR: Per Diem Expense, CR: Cash/Bank Payable
 */
class PayrollGLPostingService
{
    public function __construct(
        private readonly JournalEntryService $jeService,
        private readonly VoucherService $voucherService,
    ) {}

    // ─────────────────────────────────────────────────────────────────
    // Auto-numbering
    // ─────────────────────────────────────────────────────────────────

    public function generatePdrReference(?int $year = null): string
    {
        $year   = $year ?? now()->year;
        $prefix = FinanceSetting::get('pdr_number_prefix', 'PDR');
        $like   = "{$prefix}-{$year}-%";
        $last   = PerdiemRequest::withTrashed()->where('reference', 'like', $like)
            ->orderByRaw('LENGTH(reference) DESC')->orderBy('reference', 'desc')->value('reference');
        $seq = $last ? ((int) last(explode('-', $last))) + 1 : 1;
        return sprintf('%s-%d-%04d', $prefix, $year, $seq);
    }

    // ─────────────────────────────────────────────────────────────────
    // Build PayrollSummary from HR Payroll record
    // ─────────────────────────────────────────────────────────────────

    public function buildSummary(Payroll $payroll, array $overrides = []): PayrollSummary
    {
        $existing = PayrollSummary::where('employee_id', $payroll->employee_id)
            ->where('payroll_month', $payroll->month)
            ->where('payroll_year', $payroll->year)
            ->first();

        if ($existing) {
            return $existing;
        }

        $gross       = (float) $payroll->total_compensation;
        $allowances  = $gross - (float) $payroll->basic_salary;
        $incomeTax   = PayrollSummary::computeIncomeTax($gross);

        $pensionRate_ee = (float) FinanceSetting::get('pension_employee_rate', 7) / 100; // 7%
        $pensionRate_er = (float) FinanceSetting::get('pension_employer_rate', 11) / 100; // 11%

        $pension_ee  = round($gross * $pensionRate_ee, 2);
        $pension_er  = round($gross * $pensionRate_er, 2);
        $otherDeductions = (float) ($overrides['other_deductions'] ?? 0);
        $deductions  = $incomeTax + $pension_ee + $otherDeductions;
        $netPay      = $gross - $deductions;

        $employee = $payroll->employee;

        return PayrollSummary::create(array_merge([
            'payroll_month'       => $payroll->month,
            'payroll_year'        => $payroll->year,
            'employee_id'         => $payroll->employee_id,
            'payroll_id'          => $payroll->id,
            'department_id'       => $employee?->department_id,
            'cost_center_id'      => $overrides['cost_center_id'] ?? null,
            'project_id'          => $employee?->project_id,
            'basic_salary'        => $payroll->basic_salary,
            'allowances_total'    => $allowances,
            'gross_pay'           => $gross,
            'income_tax_withheld' => round($incomeTax, 2),
            'pension_employee'    => $pension_ee,
            'pension_employer'    => $pension_er,
            'other_deductions'    => $otherDeductions,
            'deductions_total'    => $deductions,
            'net_pay'             => $netPay,
            'employer_total_cost' => $netPay + $pension_er,
            'status'              => 'draft',
            'prepared_by'         => auth()->id(),
        ], $overrides));
    }

    // ─────────────────────────────────────────────────────────────────
    // Post PayrollSummary → GL
    // ─────────────────────────────────────────────────────────────────

    public function postToGL(PayrollSummary $summary): JournalEntry
    {
        if ($summary->isPosted()) {
            throw new \RuntimeException('This payroll summary has already been posted to the GL.');
        }

        $period = AccountingPeriod::current()
            ?? throw new \RuntimeException('No open accounting period. Please open a period first.');

        $jeRef = $this->jeService->generateReference(now()->year);

        // COA lookups — all configurable via Finance Settings
        $salaryExpense   = $this->resolveCoa('salary_expense_account',     '5100');
        $allowanceExp    = $this->resolveCoa('allowance_expense_account',   '5110');
        $pensionExpense  = $this->resolveCoa('pension_expense_account',     '5120');
        $salaryPayable   = $this->resolveCoa('salary_payable_account',      '2100');
        $pensionPayable  = $this->resolveCoa('pension_payable_account',     '2110');
        $incomeTaxPayable = $this->resolveCoa('income_tax_payable_account', '2120');

        $je = null;

        DB::transaction(function () use (
            $summary, $period, $jeRef,
            $salaryExpense, $allowanceExp, $pensionExpense,
            $salaryPayable, $pensionPayable, $incomeTaxPayable, &$je
        ) {
            $je = JournalEntry::create([
                'reference_number'     => $jeRef,
                'accounting_period_id' => $period->id,
                'transaction_date'     => now()->startOfMonth(),
                'description'          => "Payroll {$summary->monthLabel()} — {$summary->employee?->full_name}",
                'status'               => 'approved',
                'source'               => 'payroll',
                'source_type'          => PayrollSummary::class,
                'source_id'            => $summary->id,
                'prepared_by'          => auth()->id(),
                'approved_by'          => auth()->id(),
                'currency_id'          => $summary->currency_id,
                'exchange_rate_to_base'=> 1,
            ]);

            $dim = [
                'cost_center_id' => $summary->cost_center_id,
                'project_id'     => $summary->project_id,
                'donor_id'       => $summary->donor_id,
            ];

            // DR: Basic Salary Expense
            $this->line($je->id, $salaryExpense, (float)$summary->basic_salary, 0, $dim, 'Basic salary');
            // DR: Allowance Expense
            if ((float)$summary->allowances_total > 0) {
                $this->line($je->id, $allowanceExp, (float)$summary->allowances_total, 0, $dim, 'Allowances');
            }
            // DR: Pension Expense (employer portion)
            $this->line($je->id, $pensionExpense, (float)$summary->pension_employer, 0, $dim, 'Employer pension contribution');

            // CR: Net Salary Payable
            $this->line($je->id, $salaryPayable, 0, (float)$summary->net_pay, $dim, 'Net pay payable');
            // CR: Employee Pension Payable
            $this->line($je->id, $pensionPayable, 0, (float)$summary->pension_employee, $dim, 'Employee pension deduction');
            // CR: Income Tax Payable
            if ((float)$summary->income_tax_withheld > 0) {
                $this->line($je->id, $incomeTaxPayable, 0, (float)$summary->income_tax_withheld, $dim, 'Income tax withheld');
            }

            $je->load('lines');
            $this->jeService->post($je, auth()->user());

            $summary->forceFill([
                'status'           => 'journal_posted',
                'journal_entry_id' => $je->id,
            ])->save();
        });

        return $je;
    }

    // ─────────────────────────────────────────────────────────────────
    // Per-Diem Settlement → GL
    // ─────────────────────────────────────────────────────────────────

    public function postPerdiemSettlement(PerdiemSettlement $settlement): JournalEntry
    {
        if ($settlement->isClosed()) {
            throw new \RuntimeException('This settlement is already closed and posted.');
        }

        $period = AccountingPeriod::current()
            ?? throw new \RuntimeException('No open accounting period.');

        $jeRef      = $this->jeService->generateReference(now()->year);
        $request    = $settlement->perdiemRequest;
        $perdiemCoa = $this->resolveCoa('perdiem_expense_account', '5200');
        $cashCoa    = $this->resolveCoa('default_ar_account',       '1200');

        $je = null;

        DB::transaction(function () use ($settlement, $request, $period, $jeRef, $perdiemCoa, $cashCoa, &$je) {
            $je = JournalEntry::create([
                'reference_number'     => $jeRef,
                'accounting_period_id' => $period->id,
                'transaction_date'     => $settlement->settlement_date,
                'description'          => "Per Diem Settlement — {$request->reference}",
                'status'               => 'approved',
                'source'               => 'perdiem',
                'source_type'          => PerdiemSettlement::class,
                'source_id'            => $settlement->id,
                'prepared_by'          => auth()->id(),
                'approved_by'          => auth()->id(),
                'currency_id'          => $request->currency_id,
                'exchange_rate_to_base'=> 1,
            ]);

            $dim = [
                'cost_center_id' => $request->cost_center_id,
                'project_id'     => $request->project_id,
                'donor_id'       => $request->donor_id,
            ];

            $balance = (float) $settlement->balance_to_recover;

            if ($balance >= 0) {
                // Employee used more than advanced — additional payable
                $this->line($je->id, $perdiemCoa, abs($balance), 0, $dim, 'Per diem expense additional');
                $this->line($je->id, $cashCoa,     0, abs($balance), $dim, 'Per diem payable to employee');
            } else {
                // Employee owes back — recover advance
                $this->line($je->id, $cashCoa,     abs($balance), 0, $dim, 'Per diem advance recovery');
                $this->line($je->id, $perdiemCoa,  0, abs($balance), $dim, 'Per diem expense reversal');
            }

            $je->load('lines');
            $this->jeService->post($je, auth()->user());

            $settlement->forceFill([
                'status'           => 'closed',
                'journal_entry_id' => $je->id,
            ])->save();

            $request->forceFill(['status' => 'settled'])->save();
        });

        return $je;
    }

    // ─────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────

    private function resolveCoa(string $settingKey, string $fallbackCode): int
    {
        $code = FinanceSetting::get($settingKey, $fallbackCode);
        return ChartOfAccount::where('code', $code)->value('id')
            ?? throw new \RuntimeException("COA account '{$code}' not found. Configure '{$settingKey}' in Finance Settings.");
    }

    private function line(int $jeId, int $accountId, float $debit, float $credit, array $dim, string $narration): void
    {
        JournalEntryLine::create([
            'journal_entry_id' => $jeId,
            'account_id'       => $accountId,
            'debit'            => $debit,
            'credit'           => $credit,
            'cost_center_id'   => $dim['cost_center_id'] ?? null,
            'project_id'       => $dim['project_id']     ?? null,
            'donor_id'         => $dim['donor_id']       ?? null,
            'narration'        => $narration,
        ]);
    }
}

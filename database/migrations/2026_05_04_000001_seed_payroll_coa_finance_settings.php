<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Seed the six payroll Chart-of-Account setting keys into finance_settings.
 *
 * These are looked up at runtime by PayrollGLPostingService::resolveCoa().
 * The system falls back to the hardcoded account codes ('5100', '5110', etc.)
 * when the setting row is missing, but that will throw a RuntimeException if
 * those codes don't exist in chart_of_accounts either.
 *
 * Storing them as settings lets Finance managers reconfigure mappings without
 * touching code.
 */
return new class extends Migration
{
    private array $rows = [
        ['key' => 'salary_expense_account',    'group' => 'payroll', 'label' => 'Salary Expense Account (COA Code)',     'value' => '5100', 'data_type' => 'string', 'description' => 'COA code for basic salary expense. DR on payroll posting.'],
        ['key' => 'allowance_expense_account', 'group' => 'payroll', 'label' => 'Allowance Expense Account (COA Code)',  'value' => '5110', 'data_type' => 'string', 'description' => 'COA code for allowance expense. DR on payroll posting.'],
        ['key' => 'pension_expense_account',   'group' => 'payroll', 'label' => 'Pension Expense Account (COA Code)',    'value' => '5120', 'data_type' => 'string', 'description' => 'COA code for employer pension contribution expense. DR on payroll posting.'],
        ['key' => 'salary_payable_account',    'group' => 'payroll', 'label' => 'Net Salary Payable Account (COA Code)', 'value' => '2100', 'data_type' => 'string', 'description' => 'COA code for net salary payable to employees. CR on payroll posting.'],
        ['key' => 'pension_payable_account',   'group' => 'payroll', 'label' => 'Pension Payable Account (COA Code)',    'value' => '2110', 'data_type' => 'string', 'description' => 'COA code for employee pension deduction payable. CR on payroll posting.'],
        ['key' => 'income_tax_payable_account','group' => 'payroll', 'label' => 'Income Tax Payable Account (COA Code)', 'value' => '2120', 'data_type' => 'string', 'description' => 'COA code for PAYE income tax payable. CR on payroll posting.'],
    ];

    public function up(): void
    {
        foreach ($this->rows as $row) {
            DB::table('finance_settings')->upsert(
                array_merge($row, ['created_at' => now(), 'updated_at' => now()]),
                ['key'],
                ['label', 'description', 'updated_at'],
            );
        }
    }

    public function down(): void
    {
        DB::table('finance_settings')
            ->whereIn('key', array_column($this->rows, 'key'))
            ->delete();
    }
};

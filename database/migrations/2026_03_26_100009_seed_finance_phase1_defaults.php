<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ── Account Types (Ethiopian NGO standard chart of accounts) ──────
        DB::table('finance_account_types')->insert([
            ['code' => 'ASSET',     'name' => 'Asset',     'classification' => 'asset',     'normal_balance' => 'debit',  'description' => 'Resources owned or controlled by the organization.',       'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'LIABILITY', 'name' => 'Liability',  'classification' => 'liability', 'normal_balance' => 'credit', 'description' => 'Obligations and amounts owed to external parties.',          'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'EQUITY',    'name' => 'Equity',    'classification' => 'equity',    'normal_balance' => 'credit', 'description' => 'Net assets / accumulated fund balances.',                   'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'INCOME',    'name' => 'Income',    'classification' => 'income',    'normal_balance' => 'credit', 'description' => 'Revenue, grants received and other income.',                'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'EXPENSE',   'name' => 'Expense',   'classification' => 'expense',   'normal_balance' => 'debit',  'description' => 'Operational and project costs incurred by the organization.', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ── Budget Types ───────────────────────────────────────────────────
        DB::table('finance_budget_types')->insert([
            ['code' => 'OPERATIONAL',   'name' => 'Operational Budget',    'category' => 'operational',   'description' => 'Annual running costs of the organization.',              'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'PROJECT',       'name' => 'Project Budget',        'category' => 'project',       'description' => 'Budget tied to a specific project and timeline.',       'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'DONOR_FUNDED',  'name' => 'Donor-Funded Budget',   'category' => 'donor_funded',  'description' => 'Budget funded by a specific donor / grant agreement.',  'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'CAPITAL',       'name' => 'Capital Budget',        'category' => 'capital',       'description' => 'Fixed assets and long-term investment expenditures.',   'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'EMERGENCY',     'name' => 'Emergency Response',    'category' => 'emergency',     'description' => 'Rapid-response humanitarian budgets.',                  'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ── Tax Types (Ethiopian statutory rates) ─────────────────────────
        DB::table('finance_tax_types')->insert([
            ['code' => 'WHT_2',    'name' => 'Withholding Tax – 2%',    'category' => 'withholding_tax', 'default_rate' => 0.0200, 'is_automatic' => true,  'applies_to_individuals' => true,  'applies_to_organizations' => true,  'description' => 'WHT on goods and imports.',          'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'WHT_10',   'name' => 'Withholding Tax – 10%',   'category' => 'withholding_tax', 'default_rate' => 0.1000, 'is_automatic' => true,  'applies_to_individuals' => true,  'applies_to_organizations' => true,  'description' => 'WHT on services from organizations.','is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'WHT_15',   'name' => 'Withholding Tax – 15%',   'category' => 'withholding_tax', 'default_rate' => 0.1500, 'is_automatic' => true,  'applies_to_individuals' => true,  'applies_to_organizations' => false, 'description' => 'WHT on services from individuals.',  'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'VAT',      'name' => 'Value Added Tax – 15%',   'category' => 'vat',             'default_rate' => 0.1500, 'is_automatic' => true,  'applies_to_individuals' => false, 'applies_to_organizations' => true,  'description' => 'Standard Ethiopian VAT.',            'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'VAT_EXEMPT','name' => 'VAT Exempt',             'category' => 'vat',             'default_rate' => 0.0000, 'is_automatic' => false, 'applies_to_individuals' => true,  'applies_to_organizations' => true,  'description' => 'Donor-funded procurement exemptions.','is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'PENSION_EE','name' => 'Pension – Employee 7%',  'category' => 'pension',         'default_rate' => 0.0700, 'is_automatic' => true,  'applies_to_individuals' => true,  'applies_to_organizations' => false, 'description' => 'Employee pension contribution.',     'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'PENSION_ER','name' => 'Pension – Employer 11%', 'category' => 'pension',         'default_rate' => 0.1100, 'is_automatic' => true,  'applies_to_individuals' => false, 'applies_to_organizations' => true,  'description' => 'Employer pension contribution.',     'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ── Finance Settings (Ethiopian defaults) ──────────────────────────
        $etbCurrencyId = DB::table('currencies')->where('code', 'ETB')->value('id') ?? 1;

        DB::table('finance_settings')->insert([
            ['key' => 'base_currency_id',           'group' => 'general',   'label' => 'Base / Functional Currency',         'value' => $etbCurrencyId, 'data_type' => 'integer', 'description' => 'All transactions are reported in this currency.', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'fiscal_year_start_month',    'group' => 'general',   'label' => 'Fiscal Year Start Month',            'value' => '8',            'data_type' => 'integer', 'description' => 'Month number (1–12). 8 = Hamle (Ethiopian fiscal year).', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'fiscal_year_basis',          'group' => 'general',   'label' => 'Fiscal Year Calendar Basis',         'value' => 'ethiopian',    'data_type' => 'string',  'description' => 'ethiopian or gregorian.', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'petty_cash_replenish_limit', 'group' => 'general',   'label' => 'Petty Cash Replenishment Limit',     'value' => '5000.00',      'data_type' => 'decimal', 'description' => 'Maximum single petty cash replenishment amount (ETB).', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'default_vat_rate',           'group' => 'tax',       'label' => 'Default VAT Rate',                   'value' => '0.15',         'data_type' => 'decimal', 'description' => 'Applied automatically on eligible payment vouchers.', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'default_wht_goods_rate',     'group' => 'tax',       'label' => 'Default WHT Rate – Goods',           'value' => '0.02',         'data_type' => 'decimal', 'description' => 'Withholding tax on goods and imports.', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'default_wht_services_org',   'group' => 'tax',       'label' => 'Default WHT Rate – Services (Org)',  'value' => '0.10',         'data_type' => 'decimal', 'description' => 'Withholding tax on services from organizations.', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'default_wht_services_ind',   'group' => 'tax',       'label' => 'Default WHT Rate – Services (Ind)',  'value' => '0.15',         'data_type' => 'decimal', 'description' => 'Withholding tax on services from individuals.', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'pension_employee_rate',       'group' => 'payroll',   'label' => 'Pension – Employee Rate',            'value' => '0.07',         'data_type' => 'decimal', 'description' => 'Employee pension deduction (7%).', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'pension_employer_rate',       'group' => 'payroll',   'label' => 'Pension – Employer Rate',            'value' => '0.11',         'data_type' => 'decimal', 'description' => 'Employer pension contribution (11%).', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'pv_number_prefix',            'group' => 'general',   'label' => 'Payment Voucher Prefix',             'value' => 'PV',           'data_type' => 'string',  'description' => 'Auto-number prefix for payment vouchers.', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'crv_number_prefix',           'group' => 'general',   'label' => 'Cash Receipt Voucher Prefix',        'value' => 'CRV',          'data_type' => 'string',  'description' => 'Auto-number prefix for cash receipt vouchers.', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'je_number_prefix',            'group' => 'general',   'label' => 'Journal Entry Prefix',               'value' => 'JE',           'data_type' => 'string',  'description' => 'Auto-number prefix for journal entries.', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'pr_number_prefix',            'group' => 'general',   'label' => 'Payment Requisition Prefix',         'value' => 'PR',           'data_type' => 'string',  'description' => 'Auto-number prefix for payment requisitions.', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'csa_organization_name',       'group' => 'reporting', 'label' => 'Organization Name (CSA Reports)',    'value' => null,           'data_type' => 'string',  'description' => 'Legal name as registered with the CSA.', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'csa_registration_number',     'group' => 'reporting', 'label' => 'CSA Registration Number',           'value' => null,           'data_type' => 'string',  'description' => 'Official CSA registration number.', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'mof_tin_number',              'group' => 'reporting', 'label' => 'MoF TIN Number',                    'value' => null,           'data_type' => 'string',  'description' => 'Tax Identification Number for MoF reports.', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        DB::table('finance_settings')->truncate();
        DB::table('finance_tax_types')->truncate();
        DB::table('finance_budget_types')->truncate();
        DB::table('finance_account_types')->truncate();
    }
};

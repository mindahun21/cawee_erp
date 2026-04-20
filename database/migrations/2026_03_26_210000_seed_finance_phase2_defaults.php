<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Phase 2 — Seed: Financial Statement Categories + Chart of Accounts
 *
 * Inserts the standard Ethiopian NGO chart of accounts (CoA) plus the
 * Financial Statement Category (FSC) tree required for Balance Sheet,
 * Income Statement, and Cash Flow Statement generation.
 *
 * Insert order:
 *   1. FSC categories (headers first, then sub-categories)
 *   2. CoA accounts — level 0 headers first, then level 1, level 2
 *      so that parent_id foreign-key references are always satisfied.
 */
return new class extends Migration
{
    // ── Helpers ───────────────────────────────────────────────────────

    private function now(): string
    {
        return now()->toDateTimeString();
    }

    // ── Up ────────────────────────────────────────────────────────────

    public function up(): void
    {
        $this->seedFinancialStatementCategories();
        $this->seedChartOfAccounts();
    }

    // ── FSC Seed ──────────────────────────────────────────────────────

    private function seedFinancialStatementCategories(): void
    {
        $now = $this->now();

        // ── Balance Sheet categories ──────────────────────────────────
        DB::table('finance_financial_statement_categories')->insert([
            [
                'code'           => 'BS-CA',
                'name'           => 'Current Assets',
                'statement_type' => 'balance_sheet',
                'display_order'  => 10,
                'parent_id'      => null,
                'description'    => 'Cash, bank, receivables and other assets expected to be realized within 12 months.',
                'is_active'      => true,
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
            [
                'code'           => 'BS-NCA',
                'name'           => 'Non-Current Assets',
                'statement_type' => 'balance_sheet',
                'display_order'  => 20,
                'parent_id'      => null,
                'description'    => 'Fixed assets, long-term investments and other non-current resources.',
                'is_active'      => true,
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
            [
                'code'           => 'BS-CL',
                'name'           => 'Current Liabilities',
                'statement_type' => 'balance_sheet',
                'display_order'  => 30,
                'parent_id'      => null,
                'description'    => 'Obligations due within 12 months — payables, tax, pension.',
                'is_active'      => true,
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
            [
                'code'           => 'BS-NCL',
                'name'           => 'Non-Current Liabilities',
                'statement_type' => 'balance_sheet',
                'display_order'  => 40,
                'parent_id'      => null,
                'description'    => 'Long-term obligations.',
                'is_active'      => true,
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
            [
                'code'           => 'BS-NA',
                'name'           => 'Net Assets / Accumulated Fund',
                'statement_type' => 'balance_sheet',
                'display_order'  => 50,
                'parent_id'      => null,
                'description'    => 'Residual interest: unrestricted, donor-restricted, and prior-year balances.',
                'is_active'      => true,
                'created_at'     => $now,
                'updated_at'     => $now,
            ],

            // ── Income Statement categories ───────────────────────────
            [
                'code'           => 'IS-INC',
                'name'           => 'Grant & Donation Income',
                'statement_type' => 'income_statement',
                'display_order'  => 10,
                'parent_id'      => null,
                'description'    => 'Government grants, international and local donor income.',
                'is_active'      => true,
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
            [
                'code'           => 'IS-OI',
                'name'           => 'Other Income',
                'statement_type' => 'income_statement',
                'display_order'  => 20,
                'parent_id'      => null,
                'description'    => 'Service fees, interest, and miscellaneous income.',
                'is_active'      => true,
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
            [
                'code'           => 'IS-PE',
                'name'           => 'Program Expenses',
                'statement_type' => 'income_statement',
                'display_order'  => 30,
                'parent_id'      => null,
                'description'    => 'Direct program delivery costs: personnel, per diem, supplies.',
                'is_active'      => true,
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
            [
                'code'           => 'IS-AE',
                'name'           => 'Administrative & Support Expenses',
                'statement_type' => 'income_statement',
                'display_order'  => 40,
                'parent_id'      => null,
                'description'    => 'Overhead: rent, utilities, depreciation, audit fees.',
                'is_active'      => true,
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
            [
                'code'           => 'IS-FE',
                'name'           => 'Financial Expenses',
                'statement_type' => 'income_statement',
                'display_order'  => 50,
                'parent_id'      => null,
                'description'    => 'Exchange-rate losses, bank charges and interest expense.',
                'is_active'      => true,
                'created_at'     => $now,
                'updated_at'     => $now,
            ],

            // ── Cash Flow Statement categories ────────────────────────
            [
                'code'           => 'CF-OA',
                'name'           => 'Operating Activities',
                'statement_type' => 'cash_flow',
                'display_order'  => 10,
                'parent_id'      => null,
                'description'    => 'Cash flows from primary NGO operations.',
                'is_active'      => true,
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
            [
                'code'           => 'CF-IA',
                'name'           => 'Investing Activities',
                'statement_type' => 'cash_flow',
                'display_order'  => 20,
                'parent_id'      => null,
                'description'    => 'Purchase and disposal of fixed assets.',
                'is_active'      => true,
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
            [
                'code'           => 'CF-FA',
                'name'           => 'Financing Activities',
                'statement_type' => 'cash_flow',
                'display_order'  => 30,
                'parent_id'      => null,
                'description'    => 'Loans received/repaid and fund transfers.',
                'is_active'      => true,
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
        ]);
    }

    // ── CoA Seed ──────────────────────────────────────────────────────

    private function seedChartOfAccounts(): void
    {
        $now = $this->now();

        // Resolve account type IDs by code
        $types = DB::table('finance_account_types')
            ->whereIn('code', ['ASSET', 'LIABILITY', 'EQUITY', 'INCOME', 'EXPENSE'])
            ->pluck('id', 'code');

        $assetId     = $types['ASSET']     ?? null;
        $liabilityId = $types['LIABILITY'] ?? null;
        $equityId    = $types['EQUITY']    ?? null;
        $incomeId    = $types['INCOME']    ?? null;
        $expenseId   = $types['EXPENSE']   ?? null;

        // Resolve FSC IDs by code
        $fsc = DB::table('finance_financial_statement_categories')
            ->pluck('id', 'code');

        $fscBsCA  = $fsc['BS-CA']  ?? null;
        $fscBsNCA = $fsc['BS-NCA'] ?? null;
        $fscBsCL  = $fsc['BS-CL']  ?? null;
        $fscBsNA  = $fsc['BS-NA']  ?? null;
        $fscIsInc = $fsc['IS-INC'] ?? null;
        $fscIsOI  = $fsc['IS-OI']  ?? null;
        $fscIsPE  = $fsc['IS-PE']  ?? null;
        $fscIsAE  = $fsc['IS-AE']  ?? null;
        $fscIsFE  = $fsc['IS-FE']  ?? null;

        // ────────────────────────────────────────────────────────────────
        // LEVEL 0 — Root section headers (no parent)
        // ────────────────────────────────────────────────────────────────
        DB::table('finance_chart_of_accounts')->insert([
            [
                'code'                            => '1000',
                'name'                            => 'Assets',
                'account_type_id'                 => $assetId,
                'parent_id'                       => null,
                'financial_statement_category_id' => $fscBsCA,
                'currency_id'                     => null,
                'is_active'                       => true,
                'is_control_account'              => 'none',
                'is_donor_fund_account'           => false,
                'level'                           => 0,
                'is_header'                       => true,
                'notes'                           => null,
                'created_at'                      => $now,
                'updated_at'                      => $now,
                'deleted_at'                      => null,
            ],
            [
                'code'                            => '2000',
                'name'                            => 'Liabilities',
                'account_type_id'                 => $liabilityId,
                'parent_id'                       => null,
                'financial_statement_category_id' => $fscBsCL,
                'currency_id'                     => null,
                'is_active'                       => true,
                'is_control_account'              => 'none',
                'is_donor_fund_account'           => false,
                'level'                           => 0,
                'is_header'                       => true,
                'notes'                           => null,
                'created_at'                      => $now,
                'updated_at'                      => $now,
                'deleted_at'                      => null,
            ],
            [
                'code'                            => '3000',
                'name'                            => 'Net Assets / Equity',
                'account_type_id'                 => $equityId,
                'parent_id'                       => null,
                'financial_statement_category_id' => $fscBsNA,
                'currency_id'                     => null,
                'is_active'                       => true,
                'is_control_account'              => 'none',
                'is_donor_fund_account'           => false,
                'level'                           => 0,
                'is_header'                       => true,
                'notes'                           => null,
                'created_at'                      => $now,
                'updated_at'                      => $now,
                'deleted_at'                      => null,
            ],
            [
                'code'                            => '4000',
                'name'                            => 'Income',
                'account_type_id'                 => $incomeId,
                'parent_id'                       => null,
                'financial_statement_category_id' => $fscIsInc,
                'currency_id'                     => null,
                'is_active'                       => true,
                'is_control_account'              => 'none',
                'is_donor_fund_account'           => false,
                'level'                           => 0,
                'is_header'                       => true,
                'notes'                           => null,
                'created_at'                      => $now,
                'updated_at'                      => $now,
                'deleted_at'                      => null,
            ],
            [
                'code'                            => '5000',
                'name'                            => 'Expenses',
                'account_type_id'                 => $expenseId,
                'parent_id'                       => null,
                'financial_statement_category_id' => $fscIsPE,
                'currency_id'                     => null,
                'is_active'                       => true,
                'is_control_account'              => 'none',
                'is_donor_fund_account'           => false,
                'level'                           => 0,
                'is_header'                       => true,
                'notes'                           => null,
                'created_at'                      => $now,
                'updated_at'                      => $now,
                'deleted_at'                      => null,
            ],
        ]);

        // Resolve level-0 IDs
        $p = DB::table('finance_chart_of_accounts')
            ->whereIn('code', ['1000', '2000', '3000', '4000', '5000'])
            ->pluck('id', 'code');

        [$p1000, $p2000, $p3000, $p4000, $p5000] = [
            $p['1000'], $p['2000'], $p['3000'], $p['4000'], $p['5000'],
        ];

        // ────────────────────────────────────────────────────────────────
        // LEVEL 1 — Section sub-headers
        // ────────────────────────────────────────────────────────────────
        DB::table('finance_chart_of_accounts')->insert([
            // ── 1000 Assets ─────────────────────────────────────────────
            ['code' => '1100', 'name' => 'Cash & Cash Equivalents',   'account_type_id' => $assetId,     'parent_id' => $p1000, 'financial_statement_category_id' => $fscBsCA,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 1, 'is_header' => true, 'notes' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['code' => '1200', 'name' => 'Receivables & Advances',    'account_type_id' => $assetId,     'parent_id' => $p1000, 'financial_statement_category_id' => $fscBsCA,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 1, 'is_header' => true, 'notes' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['code' => '1300', 'name' => 'Prepaid Expenses',          'account_type_id' => $assetId,     'parent_id' => $p1000, 'financial_statement_category_id' => $fscBsCA,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 1, 'is_header' => true, 'notes' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['code' => '1400', 'name' => 'Inventory',                 'account_type_id' => $assetId,     'parent_id' => $p1000, 'financial_statement_category_id' => $fscBsCA,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 1, 'is_header' => true, 'notes' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['code' => '1500', 'name' => 'Fixed Assets',              'account_type_id' => $assetId,     'parent_id' => $p1000, 'financial_statement_category_id' => $fscBsNCA, 'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 1, 'is_header' => true, 'notes' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],

            // ── 2000 Liabilities ─────────────────────────────────────────
            ['code' => '2100', 'name' => 'Accounts Payable',          'account_type_id' => $liabilityId, 'parent_id' => $p2000, 'financial_statement_category_id' => $fscBsCL,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 1, 'is_header' => true, 'notes' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['code' => '2200', 'name' => 'Tax Payable',               'account_type_id' => $liabilityId, 'parent_id' => $p2000, 'financial_statement_category_id' => $fscBsCL,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 1, 'is_header' => true, 'notes' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['code' => '2300', 'name' => 'Pension & Statutory',       'account_type_id' => $liabilityId, 'parent_id' => $p2000, 'financial_statement_category_id' => $fscBsCL,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 1, 'is_header' => true, 'notes' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['code' => '2400', 'name' => 'Staff Payable',             'account_type_id' => $liabilityId, 'parent_id' => $p2000, 'financial_statement_category_id' => $fscBsCL,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 1, 'is_header' => true, 'notes' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],

            // ── 4000 Income ──────────────────────────────────────────────
            ['code' => '4100', 'name' => 'Grant Income',              'account_type_id' => $incomeId,    'parent_id' => $p4000, 'financial_statement_category_id' => $fscIsInc, 'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 1, 'is_header' => true, 'notes' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['code' => '4200', 'name' => 'Service Fee Income',        'account_type_id' => $incomeId,    'parent_id' => $p4000, 'financial_statement_category_id' => $fscIsOI,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 1, 'is_header' => true, 'notes' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],

            // ── 5000 Expenses ────────────────────────────────────────────
            ['code' => '5100', 'name' => 'Personnel Costs',           'account_type_id' => $expenseId,   'parent_id' => $p5000, 'financial_statement_category_id' => $fscIsPE,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 1, 'is_header' => true, 'notes' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['code' => '5200', 'name' => 'Per Diem & Travel',         'account_type_id' => $expenseId,   'parent_id' => $p5000, 'financial_statement_category_id' => $fscIsPE,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 1, 'is_header' => true, 'notes' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['code' => '5300', 'name' => 'Program Direct Expenses',   'account_type_id' => $expenseId,   'parent_id' => $p5000, 'financial_statement_category_id' => $fscIsPE,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 1, 'is_header' => true, 'notes' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['code' => '5400', 'name' => 'Administrative Expenses',   'account_type_id' => $expenseId,   'parent_id' => $p5000, 'financial_statement_category_id' => $fscIsAE,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 1, 'is_header' => true, 'notes' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['code' => '5500', 'name' => 'Depreciation',              'account_type_id' => $expenseId,   'parent_id' => $p5000, 'financial_statement_category_id' => $fscIsAE,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 1, 'is_header' => true, 'notes' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['code' => '5600', 'name' => 'Financial Expenses',        'account_type_id' => $expenseId,   'parent_id' => $p5000, 'financial_statement_category_id' => $fscIsFE,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 1, 'is_header' => true, 'notes' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
        ]);

        // Resolve level-1 IDs needed as parents for level-2 leaves
        $p1 = DB::table('finance_chart_of_accounts')
            ->whereIn('code', [
                '1100','1200','1300','1400','1500',
                '2100','2200','2300','2400',
                '4100','4200',
                '5100','5200','5300','5400','5500','5600',
            ])
            ->pluck('id', 'code');

        // ────────────────────────────────────────────────────────────────
        // LEVEL 1 — Equity leaf accounts (directly under 3000)
        // ────────────────────────────────────────────────────────────────
        DB::table('finance_chart_of_accounts')->insert([
            ['code' => '3100', 'name' => 'Accumulated Fund / Retained Surplus',   'account_type_id' => $equityId,    'parent_id' => $p3000, 'financial_statement_category_id' => $fscBsNA, 'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 1, 'is_header' => false, 'notes' => 'Net assets carried forward from prior fiscal years.', 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['code' => '3200', 'name' => 'Donor-Restricted Fund',                 'account_type_id' => $equityId,    'parent_id' => $p3000, 'financial_statement_category_id' => $fscBsNA, 'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => true,  'level' => 1, 'is_header' => false, 'notes' => 'Funds restricted by donor agreement for specific projects.', 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['code' => '3300', 'name' => 'Unrestricted Fund',                     'account_type_id' => $equityId,    'parent_id' => $p3000, 'financial_statement_category_id' => $fscBsNA, 'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 1, 'is_header' => false, 'notes' => 'Internally designated and general unrestricted net assets.', 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['code' => '3400', 'name' => 'Surplus / Deficit — Current Year',      'account_type_id' => $equityId,    'parent_id' => $p3000, 'financial_statement_category_id' => $fscBsNA, 'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 1, 'is_header' => false, 'notes' => 'Closed to Accumulated Fund at fiscal year-end.', 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
        ]);

        // Level-1 income leaf accounts (directly under 4200 parent or standalone)
        DB::table('finance_chart_of_accounts')->insert([
            ['code' => '4300', 'name' => 'Interest & Financial Income',   'account_type_id' => $incomeId,  'parent_id' => $p4000, 'financial_statement_category_id' => $fscIsOI, 'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 1, 'is_header' => false, 'notes' => 'Bank interest earned on ETB and foreign currency accounts.', 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['code' => '4400', 'name' => 'Miscellaneous Income',          'account_type_id' => $incomeId,  'parent_id' => $p4000, 'financial_statement_category_id' => $fscIsOI, 'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 1, 'is_header' => false, 'notes' => 'Other income not classified elsewhere.', 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
        ]);

        // ────────────────────────────────────────────────────────────────
        // LEVEL 2 — Transaction-level leaf accounts
        // ────────────────────────────────────────────────────────────────
        DB::table('finance_chart_of_accounts')->insert([
            // ── 1100 Cash & Cash Equivalents ─────────────────────────────
            ['code' => '1101', 'name' => 'Petty Cash — Head Office',            'account_type_id' => $assetId,     'parent_id' => $p1['1100'], 'financial_statement_category_id' => $fscBsCA,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 2, 'is_header' => false, 'notes' => 'Main office petty cash fund.', 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['code' => '1102', 'name' => 'Petty Cash — Regional Offices',       'account_type_id' => $assetId,     'parent_id' => $p1['1100'], 'financial_statement_category_id' => $fscBsCA,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 2, 'is_header' => false, 'notes' => 'Consolidated petty cash across all field offices.', 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['code' => '1110', 'name' => 'Cash on Hand',                        'account_type_id' => $assetId,     'parent_id' => $p1['1100'], 'financial_statement_category_id' => $fscBsCA,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 2, 'is_header' => false, 'notes' => 'Undeposited cash held by cashier.', 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['code' => '1121', 'name' => 'Bank Account — ETB (Main)',           'account_type_id' => $assetId,     'parent_id' => $p1['1100'], 'financial_statement_category_id' => $fscBsCA,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'bank', 'is_donor_fund_account' => false, 'level' => 2, 'is_header' => false, 'notes' => 'Primary Ethiopian Birr current account.', 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['code' => '1122', 'name' => 'Bank Account — USD (Operations)',     'account_type_id' => $assetId,     'parent_id' => $p1['1100'], 'financial_statement_category_id' => $fscBsCA,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'bank', 'is_donor_fund_account' => false, 'level' => 2, 'is_header' => false, 'notes' => 'USD account for international donor disbursements.', 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['code' => '1123', 'name' => 'Bank Account — EUR (Donor Grants)',   'account_type_id' => $assetId,     'parent_id' => $p1['1100'], 'financial_statement_category_id' => $fscBsCA,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'bank', 'is_donor_fund_account' => true,  'level' => 2, 'is_header' => false, 'notes' => 'EUR account for EU and European donor grants.', 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],

            // ── 1200 Receivables & Advances ──────────────────────────────
            ['code' => '1210', 'name' => 'Accounts Receivable',                 'account_type_id' => $assetId,     'parent_id' => $p1['1200'], 'financial_statement_category_id' => $fscBsCA,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'ar',   'is_donor_fund_account' => false, 'level' => 2, 'is_header' => false, 'notes' => 'Trade receivables from service activities.', 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['code' => '1220', 'name' => 'Per Diem & Travel Advances',          'account_type_id' => $assetId,     'parent_id' => $p1['1200'], 'financial_statement_category_id' => $fscBsCA,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 2, 'is_header' => false, 'notes' => 'Staff per-diem and travel advance balances pending settlement.', 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['code' => '1230', 'name' => 'Employee Cash Advances',              'account_type_id' => $assetId,     'parent_id' => $p1['1200'], 'financial_statement_category_id' => $fscBsCA,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 2, 'is_header' => false, 'notes' => 'General cash advances issued to staff pending retirement.', 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['code' => '1240', 'name' => 'Staff Loans Receivable',              'account_type_id' => $assetId,     'parent_id' => $p1['1200'], 'financial_statement_category_id' => $fscBsCA,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 2, 'is_header' => false, 'notes' => 'Employee salary advance loans.', 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['code' => '1250', 'name' => 'Due from Field Offices',              'account_type_id' => $assetId,     'parent_id' => $p1['1200'], 'financial_statement_category_id' => $fscBsCA,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 2, 'is_header' => false, 'notes' => 'HO→Field fund transfers pending field-office confirmation.', 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['code' => '1260', 'name' => 'Supplier Prepayments',                'account_type_id' => $assetId,     'parent_id' => $p1['1200'], 'financial_statement_category_id' => $fscBsCA,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 2, 'is_header' => false, 'notes' => 'Advance payments to suppliers before goods/services delivery.', 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],

            // ── 1300 Prepaid Expenses ─────────────────────────────────────
            ['code' => '1310', 'name' => 'Prepaid Office Rent',                 'account_type_id' => $assetId,     'parent_id' => $p1['1300'], 'financial_statement_category_id' => $fscBsCA,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 2, 'is_header' => false, 'notes' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['code' => '1320', 'name' => 'Prepaid Insurance',                   'account_type_id' => $assetId,     'parent_id' => $p1['1300'], 'financial_statement_category_id' => $fscBsCA,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 2, 'is_header' => false, 'notes' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],

            // ── 1400 Inventory ───────────────────────────────────────────
            ['code' => '1410', 'name' => 'Office Supplies Inventory',           'account_type_id' => $assetId,     'parent_id' => $p1['1400'], 'financial_statement_category_id' => $fscBsCA,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 2, 'is_header' => false, 'notes' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['code' => '1420', 'name' => 'Program Materials Inventory',         'account_type_id' => $assetId,     'parent_id' => $p1['1400'], 'financial_statement_category_id' => $fscBsCA,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 2, 'is_header' => false, 'notes' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],

            // ── 1500 Fixed Assets ─────────────────────────────────────────
            ['code' => '1510', 'name' => 'Furniture & Equipment',               'account_type_id' => $assetId,     'parent_id' => $p1['1500'], 'financial_statement_category_id' => $fscBsNCA, 'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 2, 'is_header' => false, 'notes' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['code' => '1520', 'name' => 'Motor Vehicles',                      'account_type_id' => $assetId,     'parent_id' => $p1['1500'], 'financial_statement_category_id' => $fscBsNCA, 'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 2, 'is_header' => false, 'notes' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['code' => '1530', 'name' => 'Computer & IT Equipment',             'account_type_id' => $assetId,     'parent_id' => $p1['1500'], 'financial_statement_category_id' => $fscBsNCA, 'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 2, 'is_header' => false, 'notes' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['code' => '1540', 'name' => 'Leasehold Improvements',              'account_type_id' => $assetId,     'parent_id' => $p1['1500'], 'financial_statement_category_id' => $fscBsNCA, 'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 2, 'is_header' => false, 'notes' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['code' => '1590', 'name' => 'Accumulated Depreciation',            'account_type_id' => $assetId,     'parent_id' => $p1['1500'], 'financial_statement_category_id' => $fscBsNCA, 'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 2, 'is_header' => false, 'notes' => 'Contra-asset — credit-normal balance. Deducted from gross fixed assets.', 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],

            // ── 2100 Accounts Payable ─────────────────────────────────────
            ['code' => '2101', 'name' => 'Trade Payables — Suppliers',          'account_type_id' => $liabilityId, 'parent_id' => $p1['2100'], 'financial_statement_category_id' => $fscBsCL,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'ap',   'is_donor_fund_account' => false, 'level' => 2, 'is_header' => false, 'notes' => 'AP control account — linked to procurement suppliers.', 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['code' => '2102', 'name' => 'Accrued Expenses',                    'account_type_id' => $liabilityId, 'parent_id' => $p1['2100'], 'financial_statement_category_id' => $fscBsCL,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 2, 'is_header' => false, 'notes' => 'Expenses incurred but not yet invoiced.', 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],

            // ── 2200 Tax Payable ──────────────────────────────────────────
            ['code' => '2210', 'name' => 'Withholding Tax Payable',             'account_type_id' => $liabilityId, 'parent_id' => $p1['2200'], 'financial_statement_category_id' => $fscBsCL,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 2, 'is_header' => false, 'notes' => 'WHT deducted from supplier and employee payments pending MoF remittance.', 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['code' => '2220', 'name' => 'VAT Payable',                         'account_type_id' => $liabilityId, 'parent_id' => $p1['2200'], 'financial_statement_category_id' => $fscBsCL,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 2, 'is_header' => false, 'notes' => 'VAT collected from service recipients pending ERCA remittance.', 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['code' => '2230', 'name' => 'Income Tax Payable',                  'account_type_id' => $liabilityId, 'parent_id' => $p1['2200'], 'financial_statement_category_id' => $fscBsCL,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 2, 'is_header' => false, 'notes' => 'PAYE income tax withheld from employee salaries.', 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],

            // ── 2300 Pension & Statutory ──────────────────────────────────
            ['code' => '2310', 'name' => 'Pension Payable — Employee (7%)',     'account_type_id' => $liabilityId, 'parent_id' => $p1['2300'], 'financial_statement_category_id' => $fscBsCL,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 2, 'is_header' => false, 'notes' => 'Employee pension contribution deducted from salary.', 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['code' => '2320', 'name' => 'Pension Payable — Employer (11%)',    'account_type_id' => $liabilityId, 'parent_id' => $p1['2300'], 'financial_statement_category_id' => $fscBsCL,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 2, 'is_header' => false, 'notes' => 'Organization pension contribution pending PSERA remittance.', 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],

            // ── 2400 Staff Payable ────────────────────────────────────────
            ['code' => '2410', 'name' => 'Net Salary Payable',                  'account_type_id' => $liabilityId, 'parent_id' => $p1['2400'], 'financial_statement_category_id' => $fscBsCL,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 2, 'is_header' => false, 'notes' => 'Net salaries due to employees after all deductions.', 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['code' => '2420', 'name' => 'Deferred Income',                     'account_type_id' => $liabilityId, 'parent_id' => $p1['2400'], 'financial_statement_category_id' => $fscBsCL,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 2, 'is_header' => false, 'notes' => 'Donor grants received in advance of the eligible expenditure period.', 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],

            // ── 4100 Grant Income ─────────────────────────────────────────
            ['code' => '4101', 'name' => 'Government Grants — Federal',         'account_type_id' => $incomeId,    'parent_id' => $p1['4100'], 'financial_statement_category_id' => $fscIsInc, 'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 2, 'is_header' => false, 'notes' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['code' => '4102', 'name' => 'International Donor Grants',          'account_type_id' => $incomeId,    'parent_id' => $p1['4100'], 'financial_statement_category_id' => $fscIsInc, 'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => true,  'level' => 2, 'is_header' => false, 'notes' => 'USAID, EU, UN, bilateral and multilateral donor funds.', 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['code' => '4103', 'name' => 'Local Donor Grants',                  'account_type_id' => $incomeId,    'parent_id' => $p1['4100'], 'financial_statement_category_id' => $fscIsInc, 'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => true,  'level' => 2, 'is_header' => false, 'notes' => 'Domestic NGOs, foundations and corporate donors.', 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],

            // ── 4200 Service Fee Income ───────────────────────────────────
            ['code' => '4210', 'name' => 'Training & Capacity Building Fees',   'account_type_id' => $incomeId,    'parent_id' => $p1['4200'], 'financial_statement_category_id' => $fscIsOI,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 2, 'is_header' => false, 'notes' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['code' => '4220', 'name' => 'Consultancy & Technical Fees',        'account_type_id' => $incomeId,    'parent_id' => $p1['4200'], 'financial_statement_category_id' => $fscIsOI,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 2, 'is_header' => false, 'notes' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],

            // ── 5100 Personnel Costs ──────────────────────────────────────
            ['code' => '5110', 'name' => 'Basic Salaries',                      'account_type_id' => $expenseId,   'parent_id' => $p1['5100'], 'financial_statement_category_id' => $fscIsPE,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 2, 'is_header' => false, 'notes' => 'Gross salary expense before deductions.', 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['code' => '5120', 'name' => 'Allowances',                          'account_type_id' => $expenseId,   'parent_id' => $p1['5100'], 'financial_statement_category_id' => $fscIsPE,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 2, 'is_header' => false, 'notes' => 'Housing, transport and other taxable/non-taxable allowances.', 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['code' => '5130', 'name' => 'Overtime Pay',                        'account_type_id' => $expenseId,   'parent_id' => $p1['5100'], 'financial_statement_category_id' => $fscIsPE,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 2, 'is_header' => false, 'notes' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['code' => '5140', 'name' => 'Employer Pension Contribution',       'account_type_id' => $expenseId,   'parent_id' => $p1['5100'], 'financial_statement_category_id' => $fscIsPE,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 2, 'is_header' => false, 'notes' => 'Employer share of pension (11%) charged to the project/program.', 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['code' => '5150', 'name' => 'Severance & Termination Benefits',    'account_type_id' => $expenseId,   'parent_id' => $p1['5100'], 'financial_statement_category_id' => $fscIsPE,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 2, 'is_header' => false, 'notes' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],

            // ── 5200 Per Diem & Travel ────────────────────────────────────
            ['code' => '5210', 'name' => 'Per Diem — Local Travel',             'account_type_id' => $expenseId,   'parent_id' => $p1['5200'], 'financial_statement_category_id' => $fscIsPE,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 2, 'is_header' => false, 'notes' => 'Daily subsistence for domestic field travel.', 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['code' => '5220', 'name' => 'Per Diem — International Travel',     'account_type_id' => $expenseId,   'parent_id' => $p1['5200'], 'financial_statement_category_id' => $fscIsPE,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 2, 'is_header' => false, 'notes' => 'Daily subsistence for international missions.', 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['code' => '5230', 'name' => 'Vehicle Fuel',                        'account_type_id' => $expenseId,   'parent_id' => $p1['5200'], 'financial_statement_category_id' => $fscIsPE,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 2, 'is_header' => false, 'notes' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['code' => '5240', 'name' => 'Transport & Logistics',               'account_type_id' => $expenseId,   'parent_id' => $p1['5200'], 'financial_statement_category_id' => $fscIsPE,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 2, 'is_header' => false, 'notes' => 'Public transport, freight and logistics costs.', 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],

            // ── 5300 Program Direct Expenses ──────────────────────────────
            ['code' => '5310', 'name' => 'Training & Capacity Building',        'account_type_id' => $expenseId,   'parent_id' => $p1['5300'], 'financial_statement_category_id' => $fscIsPE,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 2, 'is_header' => false, 'notes' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['code' => '5320', 'name' => 'Materials & Program Supplies',        'account_type_id' => $expenseId,   'parent_id' => $p1['5300'], 'financial_statement_category_id' => $fscIsPE,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 2, 'is_header' => false, 'notes' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['code' => '5330', 'name' => 'Medical & Health Supplies',           'account_type_id' => $expenseId,   'parent_id' => $p1['5300'], 'financial_statement_category_id' => $fscIsPE,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 2, 'is_header' => false, 'notes' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['code' => '5340', 'name' => 'Community & Beneficiary Support',     'account_type_id' => $expenseId,   'parent_id' => $p1['5300'], 'financial_statement_category_id' => $fscIsPE,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 2, 'is_header' => false, 'notes' => 'Direct cash transfers and in-kind support to project beneficiaries.', 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['code' => '5350', 'name' => 'Sub-Grants & Partner Transfers',      'account_type_id' => $expenseId,   'parent_id' => $p1['5300'], 'financial_statement_category_id' => $fscIsPE,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 2, 'is_header' => false, 'notes' => 'Pass-through grants disbursed to implementing partners.', 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],

            // ── 5400 Administrative Expenses ──────────────────────────────
            ['code' => '5410', 'name' => 'Office Rent & Utilities',             'account_type_id' => $expenseId,   'parent_id' => $p1['5400'], 'financial_statement_category_id' => $fscIsAE,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 2, 'is_header' => false, 'notes' => 'Office/field-site rent, electricity, water and generator fuel.', 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['code' => '5420', 'name' => 'Communication & Internet',            'account_type_id' => $expenseId,   'parent_id' => $p1['5400'], 'financial_statement_category_id' => $fscIsAE,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 2, 'is_header' => false, 'notes' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['code' => '5430', 'name' => 'Stationery & Office Supplies',        'account_type_id' => $expenseId,   'parent_id' => $p1['5400'], 'financial_statement_category_id' => $fscIsAE,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 2, 'is_header' => false, 'notes' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['code' => '5440', 'name' => 'Equipment Maintenance & Repair',      'account_type_id' => $expenseId,   'parent_id' => $p1['5400'], 'financial_statement_category_id' => $fscIsAE,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 2, 'is_header' => false, 'notes' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['code' => '5450', 'name' => 'Insurance',                           'account_type_id' => $expenseId,   'parent_id' => $p1['5400'], 'financial_statement_category_id' => $fscIsAE,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 2, 'is_header' => false, 'notes' => 'Vehicle, property and professional liability insurance.', 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['code' => '5460', 'name' => 'Audit & Legal Fees',                  'account_type_id' => $expenseId,   'parent_id' => $p1['5400'], 'financial_statement_category_id' => $fscIsAE,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 2, 'is_header' => false, 'notes' => 'External audit, statutory compliance and legal advisory fees.', 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],

            // ── 5500 Depreciation ─────────────────────────────────────────
            ['code' => '5510', 'name' => 'Depreciation — Furniture & Equipment', 'account_type_id' => $expenseId,  'parent_id' => $p1['5500'], 'financial_statement_category_id' => $fscIsAE,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 2, 'is_header' => false, 'notes' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['code' => '5520', 'name' => 'Depreciation — Motor Vehicles',       'account_type_id' => $expenseId,   'parent_id' => $p1['5500'], 'financial_statement_category_id' => $fscIsAE,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 2, 'is_header' => false, 'notes' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['code' => '5530', 'name' => 'Depreciation — IT Equipment',         'account_type_id' => $expenseId,   'parent_id' => $p1['5500'], 'financial_statement_category_id' => $fscIsAE,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 2, 'is_header' => false, 'notes' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],

            // ── 5600 Financial Expenses ───────────────────────────────────
            ['code' => '5610', 'name' => 'Exchange Rate Losses',                'account_type_id' => $expenseId,   'parent_id' => $p1['5600'], 'financial_statement_category_id' => $fscIsFE,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 2, 'is_header' => false, 'notes' => 'Losses on foreign-currency conversion to ETB.', 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['code' => '5620', 'name' => 'Bank Charges & Service Fees',         'account_type_id' => $expenseId,   'parent_id' => $p1['5600'], 'financial_statement_category_id' => $fscIsFE,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 2, 'is_header' => false, 'notes' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['code' => '5630', 'name' => 'Interest Expense',                    'account_type_id' => $expenseId,   'parent_id' => $p1['5600'], 'financial_statement_category_id' => $fscIsFE,  'currency_id' => null, 'is_active' => true, 'is_control_account' => 'none', 'is_donor_fund_account' => false, 'level' => 2, 'is_header' => false, 'notes' => 'Interest on loans and overdraft facilities.', 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
        ]);
    }

    // ── Down ──────────────────────────────────────────────────────────

    public function down(): void
    {
        // Remove CoA first (FK references FSC)
        DB::table('finance_chart_of_accounts')->truncate();
        DB::table('finance_financial_statement_categories')->truncate();
    }
};

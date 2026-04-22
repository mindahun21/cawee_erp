<?php

namespace Database\Seeders\Finance;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

/**
 * FinancePermissionsSeeder
 *
 * Creates Finance-specific named roles and grants them all generated
 * Filament Shield permissions for Finance resources.
 *
 * Run: php artisan db:seed --class=Database\\Seeders\\Finance\\FinancePermissionsSeeder
 */
class FinancePermissionsSeeder extends Seeder
{
    /**
     * Finance-specific roles and their capability levels.
     *
     * Each role accumulates its own permissions PLUS superior roles' permissions
     * via the role hierarchy enforced here.
     */
    private array $roleDefinitions = [
        'cashier' => [
            'description' => 'Petty cash payments, CRVs only',
            'resources'   => [
                'PettyCashPayment',
                'CashReceiptVoucher',
            ],
        ],
        'finance_officer' => [
            'description' => 'Create/edit all Finance docs; view GL reports',
            'resources'   => [
                'AccountType', 'AccountingPeriod', 'BudgetType', 'TaxType', 'PerdiemType',
                'FinanceSetting', 'CostCenter', 'Cashier', 'FinancialStatementCategory',
                'ChartOfAccount', 'JournalEntry', 'GeneralLedger',
                'BankAccount', 'BankAdvice', 'BankDepositSlip', 'Reconciliation', 'FundTransfer',
                'CashReceiptVoucher', 'PaymentVoucher', 'ReferencePadBook',
                'PettyCashFund', 'PettyCashPayment', 'PettyCashReplenishment',
                'PaymentRequisition', 'ApprovalHistory', 'IncomeRegister', 'Loan',
                'PayrollSummary',
                'PerdiemRequest', 'PerdiemRequestExtension', 'PerdiemSettlement', 'PerdiemTaxRule',
                'Budget', 'CostBuildup', 'DeclaredTax', 'FinancialStatement',
                'ProjectProgressPayment', 'InventoryTakingSheet',
            ],
        ],
        'finance_manager' => [
            'description' => 'All officer permissions + approve PVs/PRs + manage budgets',
            'resources'   => [], // inherits all from finance_officer
        ],
        'cfo' => [
            'description' => 'All + final approval + post-to-GL + all reports',
            'resources'   => [], // inherits all
        ],
        'internal_auditor' => [
            'description' => 'Read-only view of all Finance + full audit trail',
            'resources'   => [], // view_any + view only, handled below
        ],
    ];

    public function run(): void
    {
        $this->command?->info('🔐 Creating Finance roles and assigning permissions...');

        // ── Collect all Finance Shield-generated permissions ─────────────────
        $financePermissions = Permission::where('name', 'like', '%finance%')
            ->orWhere('name', 'like', '%chart_of_account%')
            ->orWhere('name', 'like', '%journal_entr%')
            ->orWhere('name', 'like', '%general_ledger%')
            ->orWhere('name', 'like', '%bank_%')
            ->orWhere('name', 'like', '%petty_cash%')
            ->orWhere('name', 'like', '%payment_voucher%')
            ->orWhere('name', 'like', '%cash_receipt_voucher%')
            ->orWhere('name', 'like', '%payment_requisition%')
            ->orWhere('name', 'like', '%income_register%')
            ->orWhere('name', 'like', '%payroll_summar%')
            ->orWhere('name', 'like', '%perdiem%')
            ->orWhere('name', 'like', '%budget%')
            ->orWhere('name', 'like', '%cost_buildup%')
            ->orWhere('name', 'like', '%declared_tax%')
            ->orWhere('name', 'like', '%financial_statement%')
            ->orWhere('name', 'like', '%project_progress_payment%')
            ->orWhere('name', 'like', '%inventory_taking_sheet%')
            ->orWhere('name', 'like', '%reconciliation%')
            ->orWhere('name', 'like', '%fund_transfer%')
            ->orWhere('name', 'like', '%loan%')
            ->orWhere('name', 'like', '%cost_center%')
            ->orWhere('name', 'like', '%cashier%')
            ->orWhere('name', 'like', '%account_type%')
            ->orWhere('name', 'like', '%accounting_period%')
            ->orWhere('name', 'like', '%budget_type%')
            ->orWhere('name', 'like', '%tax_type%')
            ->orWhere('name', 'like', '%perdiem_type%')
            ->orWhere('name', 'like', '%reference_pad_book%')
            ->orWhere('name', 'like', '%approval_histor%')
            ->get();

        $readOnlyPermissions = $financePermissions->filter(
            fn ($p) => str_starts_with($p->name, 'view_')
        );

        // ── Cashier ─────────────────────────────────────────────────────────
        $cashier = Role::firstOrCreate(['name' => 'cashier', 'guard_name' => 'web']);
        $cashierPerms = $financePermissions->filter(fn ($p) =>
            str_contains($p->name, 'petty_cash_payment') ||
            str_contains($p->name, 'cash_receipt_voucher')
        );
        $cashier->syncPermissions($cashierPerms);
        $this->command?->info("  ✅ cashier — {$cashierPerms->count()} permissions");

        // ── Finance Officer ──────────────────────────────────────────────────
        $officer = Role::firstOrCreate(['name' => 'finance_officer', 'guard_name' => 'web']);
        $officer->syncPermissions($financePermissions);
        $this->command?->info("  ✅ finance_officer — {$financePermissions->count()} permissions");

        // ── Finance Manager ──────────────────────────────────────────────────
        $manager = Role::firstOrCreate(['name' => 'finance_manager', 'guard_name' => 'web']);
        $manager->syncPermissions($financePermissions);
        $this->command?->info("  ✅ finance_manager — {$financePermissions->count()} permissions");

        // ── CFO ──────────────────────────────────────────────────────────────
        $cfo = Role::firstOrCreate(['name' => 'cfo', 'guard_name' => 'web']);
        $cfo->syncPermissions($financePermissions);
        $this->command?->info("  ✅ cfo — {$financePermissions->count()} permissions");

        // ── Internal Auditor — read-only ─────────────────────────────────────
        $auditor = Role::firstOrCreate(['name' => 'internal_auditor', 'guard_name' => 'web']);
        $auditor->syncPermissions($readOnlyPermissions);
        $this->command?->info("  ✅ internal_auditor — {$readOnlyPermissions->count()} read-only permissions");

        // ── Grant all Finance permissions to super_admin ──────────────────────
        $superAdmin = Role::where('name', 'super_admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($financePermissions);
            $this->command?->info("  ✅ super_admin — all Finance permissions granted");
        }

        $this->command?->info('✅ Finance permissions seeder complete.');
    }
}

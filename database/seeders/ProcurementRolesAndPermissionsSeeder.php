<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * ProcurementRolesAndPermissionsSeeder
 *
 * Seeds all Procurement-module roles and permissions.
 * Run:  php artisan db:seed --class=ProcurementRolesAndPermissionsSeeder
 */
class ProcurementRolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ── Define all procurement permissions ──────────────────────
        $permissions = [
            // Suppliers
            'view_any_supplier', 'view_supplier', 'create_supplier', 'update_supplier', 'delete_supplier',
            // Budgets
            'view_any_budget', 'view_budget', 'create_budget', 'update_budget', 'delete_budget',
            // Requisitions
            'view_any_requisition', 'view_requisition', 'create_requisition', 'update_requisition', 'delete_requisition',
            'approve_requisition_supervisor', 'approve_requisition_dept_head',
            'approve_requisition_finance', 'approve_requisition_procurement',
            'reject_requisition',
            // Tenders
            'view_any_tender', 'view_tender', 'create_tender', 'update_tender', 'delete_tender', 'publish_tender',
            // Bids
            'view_any_bid', 'view_bid', 'create_bid', 'update_bid', 'delete_bid',
            'evaluate_bid', 'award_bid',
            // Purchase Orders
            'view_any_purchase_order', 'view_purchase_order', 'create_purchase_order', 'update_purchase_order', 'delete_purchase_order',
            'approve_purchase_order_officer', 'approve_purchase_order_finance', 'approve_purchase_order_director',
            'reject_purchase_order',
            // GRN
            'view_any_goods_receipt', 'view_goods_receipt', 'create_goods_receipt', 'update_goods_receipt', 'delete_goods_receipt',
            'inspect_goods_receipt', 'approve_goods_receipt',
            // Invoices
            'view_any_invoice', 'view_invoice', 'create_invoice', 'update_invoice', 'delete_invoice',
            'approve_invoice_finance', 'approve_invoice_director', 'reject_invoice',
            // 3-Way Match
            'view_any_three_way_match', 'view_three_way_match', 'run_three_way_match',
            // Payments
            'view_any_payment', 'view_payment', 'create_payment', 'update_payment', 'delete_payment',
            'approve_payment_finance', 'approve_payment_director', 'process_payment',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // ── Create roles and assign permissions ─────────────────────
        $roles = [
            'procurement_requester' => [
                'view_any_requisition', 'view_requisition', 'create_requisition', 'update_requisition',
                'view_any_budget', 'view_budget',
                'view_any_supplier', 'view_supplier',
            ],

            'procurement_supervisor' => [
                'view_any_requisition', 'view_requisition', 'approve_requisition_supervisor', 'reject_requisition',
                'view_any_budget', 'view_budget',
                'view_any_supplier', 'view_supplier',
            ],

            'procurement_dept_head' => [
                'view_any_requisition', 'view_requisition', 'approve_requisition_dept_head', 'reject_requisition',
                'view_any_budget', 'view_budget',
                'view_any_supplier', 'view_supplier',
                'view_any_purchase_order', 'view_purchase_order',
            ],

            'procurement_evaluator' => [
                'view_any_tender', 'view_tender',
                'view_any_bid', 'view_bid', 'evaluate_bid',
                'view_any_supplier', 'view_supplier',
            ],

            'procurement_store' => [
                'view_any_purchase_order', 'view_purchase_order',
                'view_any_goods_receipt', 'view_goods_receipt',
                'create_goods_receipt', 'update_goods_receipt',
                'inspect_goods_receipt',
            ],

            'procurement_officer' => [
                // All of the above, plus
                'view_any_supplier', 'view_supplier', 'create_supplier', 'update_supplier',
                'view_any_budget', 'view_budget', 'create_budget', 'update_budget',
                'view_any_requisition', 'view_requisition', 'create_requisition', 'update_requisition',
                'approve_requisition_procurement', 'reject_requisition',
                'view_any_tender', 'view_tender', 'create_tender', 'update_tender', 'publish_tender',
                'view_any_bid', 'view_bid', 'create_bid', 'update_bid', 'evaluate_bid', 'award_bid',
                'view_any_purchase_order', 'view_purchase_order', 'create_purchase_order', 'update_purchase_order',
                'approve_purchase_order_officer',
                'view_any_goods_receipt', 'view_goods_receipt', 'create_goods_receipt', 'update_goods_receipt',
                'inspect_goods_receipt', 'approve_goods_receipt',
                'view_any_invoice', 'view_invoice', 'create_invoice', 'update_invoice',
                'view_any_three_way_match', 'view_three_way_match', 'run_three_way_match',
                'view_any_payment', 'view_payment', 'create_payment',
            ],

            'procurement_finance' => [
                'view_any_budget', 'view_budget', 'create_budget', 'update_budget',
                'view_any_requisition', 'view_requisition', 'approve_requisition_finance', 'reject_requisition',
                'view_any_purchase_order', 'view_purchase_order', 'approve_purchase_order_finance',
                'view_any_invoice', 'view_invoice', 'create_invoice', 'update_invoice',
                'approve_invoice_finance', 'reject_invoice',
                'view_any_three_way_match', 'view_three_way_match', 'run_three_way_match',
                'view_any_payment', 'view_payment', 'create_payment', 'update_payment',
                'approve_payment_finance',
            ],

            'procurement_auditor' => [
                'view_any_supplier', 'view_supplier',
                'view_any_budget', 'view_budget',
                'view_any_requisition', 'view_requisition',
                'view_any_tender', 'view_tender',
                'view_any_bid', 'view_bid',
                'view_any_purchase_order', 'view_purchase_order',
                'view_any_goods_receipt', 'view_goods_receipt',
                'view_any_invoice', 'view_invoice',
                'view_any_three_way_match', 'view_three_way_match',
                'view_any_payment', 'view_payment',
            ],

            'procurement_director' => $permissions, // full access
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($rolePermissions);
        }

        $this->command->info('✅  Procurement roles and permissions seeded successfully.');
    }
}

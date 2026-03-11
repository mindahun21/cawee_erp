<?php

namespace Database\Seeders;

use App\Models\Procurement\ProcurementApprovalWorkflow;
use Illuminate\Database\Seeder;

/**
 * Seeds the default procurement approval workflows.
 * These match the hardcoded approval stages that existed before the
 * configurable workflow system was introduced.
 *
 * Safe to run multiple times (uses firstOrCreate).
 *
 * Run:  php artisan db:seed --class=ProcurementApprovalWorkflowSeeder
 */
class ProcurementApprovalWorkflowSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            // Invoice: Finance → Director
            [
                'document_type' => 'invoice',
                'name'          => 'Supplier Invoice Approval',
                'description'   => 'Supplier invoices require Finance Manager approval followed by Director authorization.',
                'stages'        => [
                    ['stage_order' => 1, 'stage_name' => 'Finance Manager', 'required_role' => 'procurement_finance', 'can_reject' => true],
                    ['stage_order' => 2, 'stage_name' => 'Director',        'required_role' => 'procurement_director', 'can_reject' => true],
                ],
            ],

            // Purchase Order: Officer → Finance → Director
            [
                'document_type' => 'purchase_order',
                'name'          => 'Purchase Order Approval',
                'description'   => 'POs go through Procurement Officer, Finance, then Director.',
                'stages'        => [
                    ['stage_order' => 1, 'stage_name' => 'Procurement Officer', 'required_role' => 'procurement_officer',  'can_reject' => true],
                    ['stage_order' => 2, 'stage_name' => 'Finance Manager',     'required_role' => 'procurement_finance',  'can_reject' => true],
                    ['stage_order' => 3, 'stage_name' => 'Director',            'required_role' => 'procurement_director', 'can_reject' => true],
                ],
            ],

            // Requisition: Supervisor → Dept Head → Finance → Procurement
            [
                'document_type' => 'requisition',
                'name'          => 'Purchase Requisition Approval',
                'description'   => 'Requisitions require four-stage approval.',
                'stages'        => [
                    ['stage_order' => 1, 'stage_name' => 'Supervisor',           'required_role' => 'procurement_supervisor', 'can_reject' => true],
                    ['stage_order' => 2, 'stage_name' => 'Department Head',       'required_role' => 'procurement_dept_head',  'can_reject' => true],
                    ['stage_order' => 3, 'stage_name' => 'Finance',               'required_role' => 'procurement_finance',    'can_reject' => true],
                    ['stage_order' => 4, 'stage_name' => 'Procurement Officer',   'required_role' => 'procurement_officer',    'can_reject' => true],
                ],
            ],

            // Payment: Finance → Director
            [
                'document_type' => 'payment',
                'name'          => 'Payment Approval',
                'description'   => 'Payments require Finance then Director approval.',
                'stages'        => [
                    ['stage_order' => 1, 'stage_name' => 'Finance Manager', 'required_role' => 'procurement_finance',  'can_reject' => true],
                    ['stage_order' => 2, 'stage_name' => 'Director',        'required_role' => 'procurement_director', 'can_reject' => true],
                ],
            ],

            // Goods Receipt: Store → Officer
            [
                'document_type' => 'goods_receipt',
                'name'          => 'Goods Receipt Approval',
                'description'   => 'GRNs require inspection by Store and final approval by Procurement Officer.',
                'stages'        => [
                    ['stage_order' => 1, 'stage_name' => 'Store / Inspector',    'required_role' => 'procurement_store',   'can_reject' => true],
                    ['stage_order' => 2, 'stage_name' => 'Procurement Officer',  'required_role' => 'procurement_officer', 'can_reject' => true],
                ],
            ],

            // Contract: Officer → Director
            [
                'document_type' => 'contract',
                'name'          => 'Contract Approval',
                'description'   => 'Contracts require Officer review and Director sign-off.',
                'stages'        => [
                    ['stage_order' => 1, 'stage_name' => 'Procurement Officer', 'required_role' => 'procurement_officer',  'can_reject' => true],
                    ['stage_order' => 2, 'stage_name' => 'Director',            'required_role' => 'procurement_director', 'can_reject' => true],
                ],
            ],

            // Tender: Officer only
            [
                'document_type' => 'tender',
                'name'          => 'Tender Publication Approval',
                'description'   => 'Tenders require Procurement Officer approval before publication.',
                'stages'        => [
                    ['stage_order' => 1, 'stage_name' => 'Procurement Officer', 'required_role' => 'procurement_officer', 'can_reject' => true],
                ],
            ],

            // Bid: Evaluator → Officer
            [
                'document_type' => 'bid',
                'name'          => 'Bid Evaluation Approval',
                'description'   => 'Bids are evaluated then awarded by the Procurement Officer.',
                'stages'        => [
                    ['stage_order' => 1, 'stage_name' => 'Evaluator',          'required_role' => 'procurement_evaluator', 'can_reject' => true],
                    ['stage_order' => 2, 'stage_name' => 'Procurement Officer', 'required_role' => 'procurement_officer',   'can_reject' => true],
                ],
            ],
        ];

        foreach ($defaults as $workflowData) {
            $stages = $workflowData['stages'];
            unset($workflowData['stages']);

            $workflow = ProcurementApprovalWorkflow::firstOrCreate(
                ['document_type' => $workflowData['document_type']],
                array_merge($workflowData, ['is_active' => true])
            );

            // Only seed stages if they don't exist yet (don't overwrite admin customisations)
            if ($workflow->stages()->count() === 0) {
                foreach ($stages as $stage) {
                    $workflow->stages()->create($stage);
                }
            }
        }

        $this->command->info('✓ Procurement approval workflows seeded (8 workflows).');
    }
}

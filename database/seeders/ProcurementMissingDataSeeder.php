<?php

namespace Database\Seeders;

use App\Models\Procurement\Contract;
use App\Models\Procurement\ContractVersion;
use App\Models\Procurement\GoodsReceipt;
use App\Models\Procurement\Invoice;
use App\Models\Procurement\Payment;
use App\Models\Procurement\PurchaseOrder;
use App\Models\Procurement\ThreeWayMatch;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProcurementMissingDataSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::first();
        $po1   = PurchaseOrder::first();
        $po2   = PurchaseOrder::skip(1)->first();
        $grn1  = GoodsReceipt::first();
        $inv1  = Invoice::first();
        $inv2  = Invoice::skip(1)->first();

        // ── 3-Way Match ──────────────────────────────────────────────
        if ($inv1 && $po1 && $grn1 && ThreeWayMatch::count() === 0) {
            $this->command->info('  Creating 3-Way Match...');
            ThreeWayMatch::create([
                'invoice_id'       => $inv1->id,
                'purchase_order_id'=> $po1->id,
                'goods_receipt_id' => $grn1->id,
                'match_status'     => 'Matched',
                'po_amount'        => 433_090,
                'grn_amount'       => 433_090,
                'invoice_amount'   => 433_090,
                'variance'         => 0,
                'matched_by'       => $admin->id,
                'matched_at'       => now(),
            ]);
        }

        // ── Payments ─────────────────────────────────────────────────
        if ($inv1 && Payment::count() === 0) {
            $this->command->info('  Creating Payments...');

            Payment::create([
                'invoice_id'      => $inv1->id,
                'supplier_id'     => $inv1->supplier_id,
                'created_by'      => $admin->id,
                'amount'          => 433_090,
                'currency'        => 'ETB',
                'payment_method'  => 'Bank Transfer',
                'bank_name'       => 'Commercial Bank of Ethiopia',
                'scheduled_date'  => now()->addDays(7)->toDateString(),
                'status'          => 'Scheduled',
                'finance_status'  => 'Pending',
                'director_status' => 'Pending',
                'notes'           => 'Payment for laptop supply — Invoice HAIT-INV-2026-0089',
            ]);

            if ($inv2) {
                Payment::create([
                    'invoice_id'          => $inv2->id,
                    'supplier_id'         => $inv2->supplier_id,
                    'created_by'          => $admin->id,
                    'amount'              => 56_292.50,
                    'currency'            => 'ETB',
                    'payment_method'      => 'Cheque',
                    'bank_name'           => 'Cooperative Bank of Oromia',
                    'bank_reference'      => 'CHQ-0045-2026',
                    'scheduled_date'      => now()->addDays(2)->toDateString(),
                    'status'              => 'Pending Approval',
                    'finance_status'      => 'Approved',
                    'finance_approved_by' => $admin->id,
                    'finance_approved_at' => now()->subDays(1),
                    'director_status'     => 'Pending',
                    'notes'               => 'Q1 stationery invoice payment — pending Director sign-off',
                ]);
            }
        }

        // ── Contracts ─────────────────────────────────────────────────
        if (Contract::count() === 0) {
            $this->command->info('  Creating Contracts...');

            $sup1 = \App\Models\Procurement\Supplier::where('code', 'SUP-HAIT')->first();
            $sup7 = \App\Models\Procurement\Supplier::where('code', 'SUP-STL')->first();
            $sup3 = \App\Models\Procurement\Supplier::where('code', 'SUP-KIS')->first();
            $bid1 = \App\Models\Procurement\Bid::where('status', 'Awarded')->first();
            $ten1 = \App\Models\Procurement\Tender::where('status', 'Awarded')->first();

            // Active laptop contract
            $c1 = Contract::create([
                'supplier_id'            => $sup1?->id ?? $po1->supplier_id,
                'tender_id'              => $ten1?->id,
                'bid_id'                 => $bid1?->id,
                'purchase_order_id'      => $po1->id,
                'created_by'             => $admin->id,
                'title'                  => 'Supply of ICT Equipment — Dell Laptops & Accessories',
                'contract_type'          => 'Goods Supply',
                'status'                 => 'Active',
                'effective_date'         => now()->subDays(10)->toDateString(),
                'expiry_date'            => now()->addDays(355)->toDateString(),
                'supplier_signed_at'     => now()->subDays(12)->toDateString(),
                'org_signed_at'          => now()->subDays(10)->toDateString(),
                'currency'               => 'ETB',
                'contract_value'         => 433_090,
                'advance_payment_percentage' => 0,
                'payment_terms'          => 'Net 30 from accepted GRN',
                'org_signatory_name'     => 'Ato Tesfaye Kebede',
                'org_signatory_title'    => 'Procurement Director',
                'supplier_contact_person'=> 'Yonas Bekele',
                'approval_status'        => 'Approved',
                'approved_by'            => $admin->id,
                'approved_at'            => now()->subDays(10),
                'special_conditions'     => 'Warranty: 2 years on all laptop units. Defective units replaced within 7 working days.',
            ]);

            // Contract amendment
            ContractVersion::create([
                'contract_id'    => $c1->id,
                'version_number' => 1,
                'change_summary' => 'Added 2 antivirus licences for newly approved positions. Value increased by ETB 450.',
                'amended_value'  => 433_540,
                'amendment_date' => now()->subDays(5)->toDateString(),
                'amended_by'     => $admin->id,
            ]);

            // Framework contract — expiring soon (triggers dashboard warning)
            Contract::create([
                'supplier_id'            => $sup7?->id ?? $po1->supplier_id,
                'created_by'             => $admin->id,
                'title'                  => 'Courier & Freight Services Framework Agreement',
                'contract_type'          => 'Framework',
                'status'                 => 'Active',
                'effective_date'         => now()->subMonths(6)->toDateString(),
                'expiry_date'            => now()->addDays(22)->toDateString(),
                'supplier_signed_at'     => now()->subMonths(6)->subDays(2)->toDateString(),
                'org_signed_at'          => now()->subMonths(6)->toDateString(),
                'currency'               => 'ETB',
                'contract_value'         => 900_000,
                'payment_terms'          => 'Monthly invoice, paid within 15 days',
                'org_signatory_name'     => 'Ato Tesfaye Kebede',
                'org_signatory_title'    => 'Procurement Director',
                'supplier_contact_person'=> 'Solomon Mulugeta',
                'approval_status'        => 'Approved',
                'approved_by'            => $admin->id,
                'approved_at'            => now()->subMonths(6),
                'special_conditions'     => 'Max rate ETB 25/km intercity. Addis CBD flat rate ETB 350.',
            ]);

            // Pending signature contract
            Contract::create([
                'supplier_id'            => $sup3?->id ?? $po1->supplier_id,
                'created_by'             => $admin->id,
                'title'                  => 'Supply and Maintenance of Industrial Equipment',
                'contract_type'          => 'Goods Supply',
                'status'                 => 'Pending Signature',
                'effective_date'         => now()->addDays(5)->toDateString(),
                'expiry_date'            => now()->addMonths(12)->toDateString(),
                'currency'               => 'ETB',
                'contract_value'         => 650_000,
                'advance_payment_percentage' => 20,
                'payment_terms'          => '20% advance, 80% on delivery & GRN',
                'org_signatory_name'     => 'Ato Tesfaye Kebede',
                'org_signatory_title'    => 'Procurement Director',
                'supplier_contact_person'=> 'Almaz Girma',
                'approval_status'        => 'Approved',
                'approved_by'            => $admin->id,
                'approved_at'            => now()->subDays(2),
            ]);
        }

        $this->command->info('✅ Missing data seeded!');
        $this->command->info('   ✓ ' . ThreeWayMatch::count() . ' 3-Way Match records');
        $this->command->info('   ✓ ' . Payment::count()       . ' Payments');
        $this->command->info('   ✓ ' . Contract::count()      . ' Contracts');
    }
}

<?php

namespace Database\Seeders;

use App\Models\Procurement\Bid;
use App\Models\Procurement\GoodsReceipt;
use App\Models\Procurement\GoodsReceiptItem;
use App\Models\Procurement\Invoice;
use App\Models\Procurement\Payment;
use App\Models\Procurement\ProcurementApprovalRecord;
use App\Models\Procurement\ProcurementApprovalWorkflow;
use App\Models\Procurement\ProcurementBudget;
use App\Models\Procurement\ProcurementMethod;
use App\Models\Procurement\PurchaseOrder;
use App\Models\Procurement\PurchaseOrderItem;
use App\Models\Procurement\Requisition;
use App\Models\Procurement\RequisitionItem;
use App\Models\Procurement\Supplier;
use App\Models\Procurement\Tender;
use App\Models\Procurement\ThreeWayMatch;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * JSI Demo Data Seeder
 *
 * Creates 4 fully matched, end-to-end procurement scenarios — one per
 * JSI threshold tier — to demonstrate the complete P2P lifecycle during
 * the vendor selection presentation on 30 March 2026.
 *
 * Run:  php artisan db:seed --class=JsiDemoDataSeeder
 *
 * Safe to run on top of existing demo data (creates fresh records).
 */
class JsiDemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('🇪🇹  JSI Demo Data Seeder — Seeding 4 procurement tier scenarios...');

        $admin = User::first();
        if (! $admin) {
            $this->command->error('No users found — run user seeder first.');
            return;
        }
        $users = User::limit(5)->get();
        $uid   = fn () => $users->random()->id;

        // ── Ensure JSI method names exist ────────────────────────────
        foreach (['Micro Purchase', 'Simplified Procurement', 'RFQ/RFP', 'Open Competition'] as $m) {
            ProcurementMethod::firstOrCreate(['name' => $m], ['is_active' => true]);
        }

        // ── Suppliers (JSI-specific; safe to create if not present) ──
        $sup = $this->ensureSuppliers();

        // ── Budget lines ─────────────────────────────────────────────
        $yr  = (string) now()->year;
        $bud = $this->ensureBudgets($yr);

        // ╔════════════════════════════════════════════════════════════╗
        // ║  TIER 1 — MICRO PURCHASE  (< ETB 77,000)                ║
        // ║  Scenario: Office stationery & toner — ETB 45,500        ║
        // ║  Flow: PR → Single Quote → Direct PO → GRN → Invoice → Payment ║
        // ╚════════════════════════════════════════════════════════════╝
        $this->command->info('  [1/4] Micro Purchase — Office Supplies (ETB 45,500)...');

        $base = ['supervisor_status'=>'Pending','dept_head_status'=>'Pending','finance_status'=>'Pending','procurement_status'=>'Pending'];

        $r1 = Requisition::create(array_merge($base, [
            'budget_id'          => $bud['ADM']->id,
            'requested_by'       => $uid(),
            'department'         => 'Administration',
            'cost_center'        => 'CC-ADM-001',
            'budget_code'        => $bud['ADM']->code,
            'category'           => 'Goods',
            'procurement_method' => 'Micro Purchase',
            'required_by_date'   => now()->addDays(7)->toDateString(),
            'delivery_location'  => 'Administration Office, HQ Building — Room 102',
            'justification'      => 'Urgent replenishment of office stationery and printer toner. Total < ETB 77,000 — qualifies for Micro Purchase. Single quotation obtained from approved vendor.',
            'estimated_total'    => 45_500,
            'overall_status'     => Requisition::STATUS_APPROVED,
            'supervisor_status'  => 'Approved', 'supervisor_approved_by' => $admin->id, 'supervisor_approved_at' => now()->subDays(5),
            'dept_head_status'   => 'Approved', 'dept_head_approved_by'  => $admin->id, 'dept_head_approved_at'  => now()->subDays(4),
            'finance_status'     => 'Approved', 'finance_approved_by'    => $admin->id, 'finance_approved_at'    => now()->subDays(3),
            'procurement_status' => 'Approved', 'procurement_approved_by'=> $admin->id, 'procurement_approved_at'=> now()->subDays(2),
        ]));
        foreach ([
            ['A4 Copy Paper (80gsm) — Box of 5 Reams',    20, 'Box',  550,  11_000],
            ['HP LaserJet Toner CF217A (Compatible)',       5, 'Unit', 2_800, 14_000],
            ['Ballpoint Pens — Box of 50',                 20, 'Box',  180,   3_600],
            ['Highlighter Set (4 Colors)',                 25, 'Set',   95,   2_375],
            ['Staples 26/6 (Box of 5,000)',                10, 'Box',  150,   1_500],
            ['Correction Fluid (Tipp-Ex) — 5 Pack',       15, 'Pack',  85,   1_275],
            ['Ruled Notebooks A4 — 100 pages',             30, 'Unit',  45,   1_350],
            ['Dry Erase Markers — Box of 10',              10, 'Box', 110,    1_100],
            ['Extension Cord 5-Socket 5m',                  5, 'Unit', 250,   1_250],
            ['File Folders A4 — Pack of 50',                5, 'Pack', 210,   1_050],
        ] as [$d,$q,$u,$up,$tp]) {
            RequisitionItem::create(['requisition_id'=>$r1->id,'description'=>$d,'quantity'=>$q,'unit'=>$u,'estimated_unit_price'=>$up,'estimated_total'=>$tp]);
        }
        $this->seedApprovalRecords($r1, 'requisition', $admin, 'Approved');

        $po1 = PurchaseOrder::create([
            'supplier_id'      => $sup['stationery']->id,
            'requisition_id'   => $r1->id,
            'created_by'       => $admin->id,
            'order_date'       => now()->subDays(1)->toDateString(),
            'delivery_date'    => now()->addDays(3)->toDateString(),
            'delivery_location'=> 'Administration Office, HQ Building — Room 102',
            'payment_terms'    => 'Payment on Delivery (< ETB 77K Micro Purchase)',
            'currency'         => 'ETB',
            'notes'            => 'MICRO PURCHASE — Single quotation. No competitive process required. Justification documented on file. Supplier: National Office Supplies PLC.',
            'overall_status'   => PurchaseOrder::STATUS_SENT,
            'procurement_officer_status' => 'Approved', 'procurement_officer_approved_by' => $admin->id, 'procurement_officer_approved_at' => now()->subDays(2),
            'finance_status'   => 'Approved', 'finance_approved_by'  => $admin->id, 'finance_approved_at'  => now()->subDays(1),
            'director_status'  => 'Approved', 'director_approved_by' => $admin->id, 'director_approved_at' => now()->subDays(1),
        ]);
        $poi1 = [];
        foreach ([
            ['A4 Copy Paper (80gsm) — Box of 5 Reams',    20, 550],
            ['HP LaserJet Toner CF217A (Compatible)',       5, 2_800],
            ['Ballpoint Pens — Box of 50',                 20, 180],
            ['Highlighter Set (4 Colors)',                 25,  95],
            ['Staples 26/6 (5,000)',                       10, 150],
        ] as [$d,$q,$up]) {
            $poi1[] = PurchaseOrderItem::create(['purchase_order_id'=>$po1->id,'description'=>$d,'quantity'=>$q,'unit_price'=>$up,'total_price'=>$q*$up]);
        }
        $this->seedApprovalRecords($po1, 'purchase_order', $admin, 'Approved');

        $grn1 = GoodsReceipt::create([
            'purchase_order_id'  => $po1->id,
            'received_by'        => $admin->id,
            'receipt_date'       => now()->toDateString(),
            'delivery_location'  => 'Administration Office, HQ Building — Room 102',
            'delivery_note_number'=> 'DN-NOS-2026-0114',
            'overall_condition'  => 'Good',
            'status'             => 'Accepted',
            'inspection_notes'   => 'All items delivered in good condition. Quantities verified against PO. Toner cartridges sealed in original packaging.',
            'inspected_by'       => $admin->id, 'inspected_at' => now()->subHours(4),
            'approved_by'        => $admin->id, 'approved_at'  => now()->subHours(2),
        ]);
        foreach ($poi1 as $item) {
            GoodsReceiptItem::create(['goods_receipt_id'=>$grn1->id,'po_item_id'=>$item->id,'received_quantity'=>$item->quantity,'accepted_quantity'=>$item->quantity,'rejected_quantity'=>0,'condition'=>'Pass','inspection_remarks'=>'Received in good condition']);
        }

        $inv1 = Invoice::create([
            'purchase_order_id'     => $po1->id,
            'supplier_id'           => $sup['stationery']->id,
            'created_by'            => $admin->id,
            'supplier_invoice_number'=> 'NOS-INV-2026-0551',
            'invoice_date'          => now()->toDateString(),
            'due_date'              => now()->addDays(3)->toDateString(),
            'subtotal'              => 38_575,
            'tax_amount'            => 5_786.25,
            'total_amount'          => 44_361.25,
            'currency'              => 'ETB',
            'status'                => Invoice::STATUS_MATCHED,
            'finance_status'        => 'Approved', 'finance_approved_by' => $admin->id, 'finance_approved_at' => now(),
            'director_status'       => 'Approved', 'director_approved_by'=> $admin->id, 'director_approved_at'=> now(),
            'notes'                 => 'Micro Purchase — invoice matched to PO & GRN. 3-way match passed.',
        ]);
        ThreeWayMatch::create(['invoice_id'=>$inv1->id,'purchase_order_id'=>$po1->id,'goods_receipt_id'=>$grn1->id,'match_status'=>'Matched','po_amount'=>44_361.25,'grn_amount'=>44_361.25,'invoice_amount'=>44_361.25,'variance'=>0,'matched_by'=>$admin->id,'matched_at'=>now()]);
        Payment::create(['invoice_id'=>$inv1->id,'supplier_id'=>$sup['stationery']->id,'created_by'=>$admin->id,'amount'=>44_361.25,'currency'=>'ETB','payment_method'=>'Cheque','bank_name'=>'Cooperative Bank of Oromia','scheduled_date'=>now()->addDays(3)->toDateString(),'status'=>'Approved','finance_status'=>'Approved','finance_approved_by'=>$admin->id,'finance_approved_at'=>now(),'director_status'=>'Approved','director_approved_by'=>$admin->id,'director_approved_at'=>now(),'notes'=>'MICRO PURCHASE payment — Office Supplies Q2 2026']);
        $this->command->info('     ✓ Micro Purchase chain complete (PR→PO→GRN→Invoice→Payment)');


        // ╔════════════════════════════════════════════════════════════╗
        // ║  TIER 2 — SIMPLIFIED PROCUREMENT  (ETB 77K – 1.54M)     ║
        // ║  Scenario: Medical Supplies & Equipment — ETB 320,000    ║
        // ║  Flow: PR → RFQ (3 quotes) → Price Analysis → PO → GRN → Invoice → Payment ║
        // ╚════════════════════════════════════════════════════════════╝
        $this->command->info('  [2/4] Simplified Procurement — Medical Supplies (ETB 320,000)...');

        $r2 = Requisition::create(array_merge($base, [
            'budget_id'          => $bud['MED']->id,
            'requested_by'       => $uid(),
            'department'         => 'Health & Safety',
            'cost_center'        => 'CC-HLT-001',
            'budget_code'        => $bud['MED']->code,
            'category'           => 'Goods',
            'procurement_method' => 'Simplified Procurement',
            'required_by_date'   => now()->addDays(21)->toDateString(),
            'delivery_location'  => 'Medical Store, Ground Floor, HQ Building',
            'justification'      => 'Procurement of medical consumables and first-aid equipment for distribution to 4 field health facilities in Addis Ababa. Estimated at ETB 320,000 — requires minimum 3 written quotations per JSI Simplified Procurement rules (ETB 77,000–1,539,846).',
            'estimated_total'    => 320_000,
            'overall_status'     => Requisition::STATUS_APPROVED,
            'supervisor_status'  => 'Approved', 'supervisor_approved_by' => $admin->id, 'supervisor_approved_at' => now()->subDays(12),
            'dept_head_status'   => 'Approved', 'dept_head_approved_by'  => $admin->id, 'dept_head_approved_at'  => now()->subDays(10),
            'finance_status'     => 'Approved', 'finance_approved_by'    => $admin->id, 'finance_approved_at'    => now()->subDays(8),
            'procurement_status' => 'Approved', 'procurement_approved_by'=> $admin->id, 'procurement_approved_at'=> now()->subDays(7),
        ]));
        foreach ([
            ['Digital Thermometer (Non-Contact IR)', 50, 'Unit', 1_850, 92_500],
            ['Blood Pressure Monitor (Automatic)',   20, 'Unit', 4_200, 84_000],
            ['Pulse Oximeter (Fingertip)',           30, 'Unit', 1_600, 48_000],
            ['First Aid Kit (Complete — 100-item)',  15, 'Unit', 2_800, 42_000],
            ['Surgical Gloves — Box of 100 (L)',     50, 'Box',    420, 21_000],
            ['Alcohol-Based Hand Sanitizer 1L',      40, 'Unit',    85,  3_400],
            ['N95 Respirator Masks — Box of 20',     20, 'Box',    180,  3_600],
            ['Disposable Face Masks — Box of 50',    30, 'Box',     95,  2_850],
            ['First Aid Bandages Assorted Pack',     60, 'Pack',    110,  6_600],
            ['Sterile Gauze Pads — Pack of 50',      40, 'Pack',    153,  6_120],
            ['Antiseptic Solution (Dettol 500ml)',   40, 'Unit',    105,  4_200],
            ['Clinical Waste Bags — Box of 100',     15, 'Box',    378,  5_670],
        ] as [$d,$q,$u,$up,$tp]) {
            RequisitionItem::create(['requisition_id'=>$r2->id,'description'=>$d,'quantity'=>$q,'unit'=>$u,'estimated_unit_price'=>$up,'estimated_total'=>$tp]);
        }
        $this->seedApprovalRecords($r2, 'requisition', $admin, 'Approved');

        // RFQ Tender for 3 supplier quotes
        $t2 = Tender::create([
            'requisition_id'     => $r2->id,
            'title'              => 'RFQ — Supply of Medical Consumables & Equipment (JSI Simplified Procurement)',
            'description'        => 'Request for Quotation for medical consumables and first-aid equipment for distribution to JSI health facilities. Minimum 3 quotations required per JSI Simplified Procurement procedures. Estimated value: ETB 320,000.',
            'method'             => 'RFQ',
            'status'             => 'Awarded',
            'visibility'         => 'invite_only',
            'issue_date'         => now()->subDays(14)->toDateString(),
            'submission_deadline'=> now()->subDays(7)->toDateString(),
            'opening_date'       => now()->subDays(6)->toDateString(),
            'award_date'         => now()->subDays(3)->toDateString(),
            'estimated_value'    => 320_000,
            'currency'           => 'ETB',
            'created_by'         => $admin->id,
            'terms_and_conditions'=> 'Quotations must be valid for 60 days. Prices must include VAT. Delivery within 14 days of PO issuance. All items must be accompanied by a Certificate of Conformity.',
        ]);

        // 3 competitive quotations (bids) — Supplier 2 is awarded
        Bid::create(['tender_id'=>$t2->id,'supplier_id'=>$sup['medical']->id,'reference_number'=>'AAMS-RFQ-2026-041','bid_amount'=>315_800,'currency'=>'ETB','submission_date'=>now()->subDays(8)->toDateString(),'validity_date'=>now()->addDays(52)->toDateString(),'status'=>'Awarded','technical_score'=>95,'financial_score'=>96,'composite_score'=>95.5,'delivery_days'=>12,'notes'=>'All items from CE-certified manufacturers. 12-month warranty on electronic devices.']);
        Bid::create(['tender_id'=>$t2->id,'supplier_id'=>$sup['stationery']->id,'reference_number'=>'NOS-RFQ-2026-019','bid_amount'=>338_200,'currency'=>'ETB','submission_date'=>now()->subDays(8)->toDateString(),'validity_date'=>now()->addDays(52)->toDateString(),'status'=>'Rejected','technical_score'=>82,'financial_score'=>78,'composite_score'=>80,'delivery_days'=>18,'notes'=>'Missing CE certification for electronic items.']);
        Bid::create(['tender_id'=>$t2->id,'supplier_id'=>$sup['general']->id,'reference_number'=>'EBG-RFQ-2026-007','bid_amount'=>327_500,'currency'=>'ETB','submission_date'=>now()->subDays(8)->toDateString(),'validity_date'=>now()->addDays(52)->toDateString(),'status'=>'Rejected','technical_score'=>88,'financial_score'=>84,'composite_score'=>86,'delivery_days'=>15,'notes'=>'Good technical score but higher price than awarded supplier.']);

        $po2 = PurchaseOrder::create([
            'supplier_id'      => $sup['medical']->id,
            'requisition_id'   => $r2->id,
            'tender_id'        => $t2->id,
            'created_by'       => $admin->id,
            'order_date'       => now()->subDays(2)->toDateString(),
            'delivery_date'    => now()->addDays(10)->toDateString(),
            'delivery_location'=> 'Medical Store, Ground Floor, HQ Building',
            'payment_terms'    => 'Net 30 from accepted GRN',
            'currency'         => 'ETB',
            'notes'            => 'SIMPLIFIED PROCUREMENT — 3 quotations obtained & compared. Price analysis completed. AAMS awarded as lowest responsive bidder. Ref: RFQ T-' . $t2->tender_number,
            'overall_status'   => PurchaseOrder::STATUS_SENT,
            'procurement_officer_status' => 'Approved', 'procurement_officer_approved_by' => $admin->id, 'procurement_officer_approved_at' => now()->subDays(3),
            'finance_status'   => 'Approved', 'finance_approved_by'  => $admin->id, 'finance_approved_at'  => now()->subDays(2),
            'director_status'  => 'Approved', 'director_approved_by' => $admin->id, 'director_approved_at' => now()->subDays(1),
        ]);
        $poi2 = [];
        foreach ([
            ['Digital Thermometer (Non-Contact IR)',        50, 'Unit', 1_750],
            ['Blood Pressure Monitor (Automatic)',          20, 'Unit', 3_990],
            ['Pulse Oximeter (Fingertip)',                  30, 'Unit', 1_520],
            ['First Aid Kit (Complete — 100-item)',         15, 'Unit', 2_650],
            ['Surgical Gloves — Box of 100 (L)',            50, 'Box',    398],
            ['Alcohol-Based Hand Sanitizer 1L',             40, 'Unit',    80],
        ] as [$d,$q,$u,$up]) {
            $poi2[] = PurchaseOrderItem::create(['purchase_order_id'=>$po2->id,'description'=>$d,'quantity'=>$q,'unit'=>$u,'unit_price'=>$up,'total_price'=>$q*$up]);
        }
        $this->seedApprovalRecords($po2, 'purchase_order', $admin, 'Approved');

        $grn2 = GoodsReceipt::create([
            'purchase_order_id'  => $po2->id,
            'received_by'        => $admin->id,
            'receipt_date'       => now()->subDays(1)->toDateString(),
            'delivery_location'  => 'Medical Store, Ground Floor, HQ Building',
            'delivery_note_number'=> 'DN-AAMS-2026-0223',
            'overall_condition'  => 'Good',
            'status'             => 'Accepted',
            'inspection_notes'   => 'All 6 line items verified against PO. Electronic devices (thermometers, BP monitors, oximeters) tested and functional. CE certificates verified and filed. Quantities match delivery note.',
            'inspected_by' => $admin->id, 'inspected_at' => now()->subDays(1),
            'approved_by'  => $admin->id, 'approved_at'  => now()->subHours(6),
        ]);
        foreach ($poi2 as $item) {
            GoodsReceiptItem::create(['goods_receipt_id'=>$grn2->id,'po_item_id'=>$item->id,'received_quantity'=>$item->quantity,'accepted_quantity'=>$item->quantity,'rejected_quantity'=>0,'condition'=>'Pass','inspection_remarks'=>'Inspected — condition good, CE compliance verified']);
        }

        $sub2 = $poi2[0]->total_price + $poi2[1]->total_price + $poi2[2]->total_price + $poi2[3]->total_price + $poi2[4]->total_price + $poi2[5]->total_price;
        $tax2 = round($sub2 * 0.15, 2);
        $inv2 = Invoice::create([
            'purchase_order_id'      => $po2->id,
            'supplier_id'            => $sup['medical']->id,
            'created_by'             => $admin->id,
            'supplier_invoice_number'=> 'AAMS-INV-2026-0187',
            'invoice_date'           => now()->toDateString(),
            'due_date'               => now()->addDays(30)->toDateString(),
            'subtotal'               => $sub2,
            'tax_amount'             => $tax2,
            'total_amount'           => $sub2 + $tax2,
            'currency'               => 'ETB',
            'status'                 => Invoice::STATUS_APPROVED,
            'finance_status'         => 'Approved', 'finance_approved_by' => $admin->id, 'finance_approved_at' => now(),
            'director_status'        => 'Approved', 'director_approved_by'=> $admin->id, 'director_approved_at'=> now(),
            'notes'                  => 'SIMPLIFIED PROCUREMENT — 3-way match passed. Invoice approved for payment.',
        ]);
        ThreeWayMatch::create(['invoice_id'=>$inv2->id,'purchase_order_id'=>$po2->id,'goods_receipt_id'=>$grn2->id,'match_status'=>'Matched','po_amount'=>$sub2+$tax2,'grn_amount'=>$sub2+$tax2,'invoice_amount'=>$sub2+$tax2,'variance'=>0,'matched_by'=>$admin->id,'matched_at'=>now()]);
        Payment::create(['invoice_id'=>$inv2->id,'supplier_id'=>$sup['medical']->id,'created_by'=>$admin->id,'amount'=>$sub2+$tax2,'currency'=>'ETB','payment_method'=>'Bank Transfer','bank_name'=>'Commercial Bank of Ethiopia','scheduled_date'=>now()->addDays(30)->toDateString(),'status'=>'Scheduled','finance_status'=>'Approved','finance_approved_by'=>$admin->id,'finance_approved_at'=>now(),'director_status'=>'Approved','director_approved_by'=>$admin->id,'director_approved_at'=>now(),'notes'=>'Payment — Medical Supplies Q2 2026 (Simplified Procurement)']);
        $this->command->info('     ✓ Simplified Procurement chain complete (PR→3 Quotes→PO→GRN→Invoice→Payment)');


        // ╔════════════════════════════════════════════════════════════╗
        // ║  TIER 3 — RFQ / RFP BASED  (ETB 1.54M – 38.5M)         ║
        // ║  Scenario: Solar Power & Generator System — ETB 4,800,000 ║
        // ║  Flow: PR → Formal RFP → 3 bids + evaluation → PO → GRN → Invoice → Payment ║
        // ╚════════════════════════════════════════════════════════════╝
        $this->command->info('  [3/4] RFQ/RFP Procurement — Solar Power System (ETB 4,800,000)...');

        $r3 = Requisition::create(array_merge($base, [
            'budget_id'          => $bud['INFRA']->id,
            'requested_by'       => $uid(),
            'department'         => 'Facilities Management',
            'cost_center'        => 'CC-FAC-002',
            'budget_code'        => $bud['INFRA']->code,
            'category'           => 'Works',
            'procurement_method' => 'RFQ/RFP',
            'required_by_date'   => now()->addDays(60)->toDateString(),
            'delivery_location'  => 'JSI Regional Office Complex, Bole Sub-City, Addis Ababa',
            'justification'      => 'Supply and installation of a 50KW hybrid solar power system with 100KVA backup generator for JSI Regional Office Complex. Estimated ETB 4,800,000 — requires formal RFP and minimum 3 bids with full technical and financial evaluation per JSI procurement rules (ETB 1,540,000–38,499,846).',
            'estimated_total'    => 4_800_000,
            'overall_status'     => Requisition::STATUS_APPROVED,
            'supervisor_status'  => 'Approved', 'supervisor_approved_by' => $admin->id, 'supervisor_approved_at' => now()->subDays(25),
            'dept_head_status'   => 'Approved', 'dept_head_approved_by'  => $admin->id, 'dept_head_approved_at'  => now()->subDays(22),
            'finance_status'     => 'Approved', 'finance_approved_by'    => $admin->id, 'finance_approved_at'    => now()->subDays(19),
            'procurement_status' => 'Approved', 'procurement_approved_by'=> $admin->id, 'procurement_approved_at'=> now()->subDays(18),
        ]));
        foreach ([
            ['50KW Hybrid Solar PV System (panels, inverter, mounting)', 1, 'System', 2_800_000, 2_800_000],
            ['100 kVA Soundproofed Diesel Generator — Perkins Engine',   1, 'Unit',   1_200_000, 1_200_000],
            ['200 kAh Lithium Battery Storage Bank',                     1, 'System',   480_000,   480_000],
            ['Automatic Transfer Switch (ATS) 415V 250A',                1, 'Unit',    95_000,    95_000],
            ['Cable Trays, Conduit, Wiring & Electrical Materials',     1, 'Lot',    125_000,   125_000],
            ['Civil Works — Concrete Pad & Security Enclosure',          1, 'Lot',     60_000,    60_000],
            ['Professional Installation, Testing & Commissioning',       1, 'Service', 40_000,    40_000],
        ] as [$d,$q,$u,$up,$tp]) {
            RequisitionItem::create(['requisition_id'=>$r3->id,'description'=>$d,'quantity'=>$q,'unit'=>$u,'estimated_unit_price'=>$up,'estimated_total'=>$tp]);
        }
        $this->seedApprovalRecords($r3, 'requisition', $admin, 'Approved');

        $t3 = Tender::create([
            'requisition_id'      => $r3->id,
            'title'               => 'RFP — Supply & Installation of 50KW Hybrid Solar Power System with 100KVA Backup Generator',
            'description'         => 'JSI invites technically capable firms to submit proposals for supply and installation of a 50KW hybrid solar PV system with battery storage and 100KVA backup generator. Scope includes civil works, full installation, testing, commissioning, and 2-year maintenance service. Technical and financial proposals must be submitted separately.',
            'method'              => 'RFP',
            'status'              => 'Awarded',
            'visibility'          => 'public',
            'issue_date'          => now()->subDays(30)->toDateString(),
            'submission_deadline' => now()->subDays(10)->toDateString(),
            'opening_date'        => now()->subDays(9)->toDateString(),
            'award_date'          => now()->subDays(3)->toDateString(),
            'estimated_value'    => 4_800_000,
            'currency'            => 'ETB',
            'created_by'          => $admin->id,
            'terms_and_conditions'=> 'Bids must remain valid 90 days from submission deadline. 5% bid security required (acceptable forms: bank guarantee or CPO). Mandatory site visit scheduled 5 days after RFP issue. Technical proposals weighted 60%, financial 40%. Award to highest combined score.',
        ]);

        // 3 bids with full technical/financial scoring
        $bid3a = Bid::create(['tender_id'=>$t3->id,'supplier_id'=>$sup['engineering']->id,'reference_number'=>'AEC-RFP-2026-015','bid_amount'=>4_720_000,'currency'=>'ETB','submission_date'=>now()->subDays(11)->toDateString(),'validity_date'=>now()->addDays(79)->toDateString(),'status'=>'Awarded','technical_score'=>91,'financial_score'=>93,'composite_score'=>91.8,'delivery_days'=>45,'notes'=>'Leading Ethiopian renewable energy firm. Similar completed projects: JSI Hawassa Office (2024), USAID Bahir Dar Office (2023). 2-year maintenance SLA included.']);
        Bid::create(['tender_id'=>$t3->id,'supplier_id'=>$sup['it']->id,'reference_number'=>'HAIT-RFP-2026-008','bid_amount'=>4_950_000,'currency'=>'ETB','submission_date'=>now()->subDays(11)->toDateString(),'validity_date'=>now()->addDays(79)->toDateString(),'status'=>'Rejected','technical_score'=>88,'financial_score'=>85,'composite_score'=>86.8,'delivery_days'=>50,'notes'=>'Good technical proposal but higher price and less solar experience.']);
        Bid::create(['tender_id'=>$t3->id,'supplier_id'=>$sup['general']->id,'reference_number'=>'EBG-RFP-2026-022','bid_amount'=>4_845_000,'currency'=>'ETB','submission_date'=>now()->subDays(11)->toDateString(),'validity_date'=>now()->addDays(79)->toDateString(),'status'=>'Rejected','technical_score'=>78,'financial_score'=>90,'composite_score'=>82.8,'delivery_days'=>55,'notes'=>'Lower technical score — limited proof of similar-scale solar installations.']);

        $po3 = PurchaseOrder::create([
            'supplier_id'      => $sup['engineering']->id,
            'requisition_id'   => $r3->id,
            'tender_id'        => $t3->id,
            'bid_id'           => $bid3a->id,
            'created_by'       => $admin->id,
            'order_date'       => now()->subDays(2)->toDateString(),
            'delivery_date'    => now()->addDays(45)->toDateString(),
            'delivery_location'=> 'JSI Regional Office Complex, Bole Sub-City, Addis Ababa',
            'payment_terms'    => '30% Advance · 40% at 50% Completion · 30% on Final Acceptance',
            'currency'         => 'ETB',
            'notes'            => 'RFP/RFP PROCUREMENT — Formal tender published. 3 proposals received and evaluated. Abay Engineering & Consultancy PLC awarded (composite score 91.8%). Payment structured as per milestone schedule. 2-year maintenance SLA included.',
            'overall_status'   => PurchaseOrder::STATUS_APPROVED,
            'procurement_officer_status' => 'Approved', 'procurement_officer_approved_by' => $admin->id, 'procurement_officer_approved_at' => now()->subDays(3),
            'finance_status'   => 'Approved', 'finance_approved_by'  => $admin->id, 'finance_approved_at'  => now()->subDays(2),
            'director_status'  => 'Approved', 'director_approved_by' => $admin->id, 'director_approved_at' => now()->subDays(1),
        ]);
        $poi3 = [];
        foreach ([
            ['50KW Hybrid Solar PV System (supply, install & commission)',1, 'System', 2_750_000],
            ['100 kVA Soundproofed Diesel Generator — Perkins Engine',    1, 'Unit',   1_150_000],
            ['200 kAh Lithium Battery Storage Bank',                      1, 'System',   460_000],
            ['Automatic Transfer Switch (ATS) 415V 250A',                 1, 'Unit',    90_000],
            ['Cable, Conduit & Electrical Works',                          1, 'Lot',    120_000],
            ['Civil Works & Security Enclosure',                           1, 'Lot',     55_000],
            ['Testing, Commissioning & Staff Training',                   1, 'Service',  45_000],
            ['2-Year Maintenance Service Agreement',                       1, 'Service',  50_000],
        ] as [$d,$q,$u,$up]) {
            $poi3[] = PurchaseOrderItem::create(['purchase_order_id'=>$po3->id,'description'=>$d,'quantity'=>$q,'unit'=>$u,'unit_price'=>$up,'total_price'=>$q*$up]);
        }
        $this->seedApprovalRecords($po3, 'purchase_order', $admin, 'Approved');

        // GRN — partial (phase 1 of installation; final items pending)
        $grn3 = GoodsReceipt::create([
            'purchase_order_id'   => $po3->id,
            'received_by'         => $admin->id,
            'receipt_date'        => now()->toDateString(),
            'delivery_location'   => 'JSI Regional Office Complex, Bole Sub-City',
            'delivery_note_number'=> 'DN-AEC-2026-0031',
            'overall_condition'   => 'Good',
            'status'              => 'Accepted',
            'inspection_notes'    => 'Phase 1 delivery: Solar panels (200 units), inverter units, and battery bank received and inspected. Serial numbers recorded. Generator delivered and test-run completed — output verified at 92KVA. Civil works 100% complete. Installation in progress.',
            'inspected_by' => $admin->id, 'inspected_at' => now()->subHours(3),
            'approved_by'  => $admin->id, 'approved_at'  => now()->subHours(1),
        ]);
        foreach ([$poi3[0],$poi3[1],$poi3[2],$poi3[4],$poi3[5]] as $item) {
            GoodsReceiptItem::create(['goods_receipt_id'=>$grn3->id,'po_item_id'=>$item->id,'received_quantity'=>$item->quantity,'accepted_quantity'=>$item->quantity,'rejected_quantity'=>0,'condition'=>'Pass','inspection_remarks'=>'Phase 1 — received and inspected']);
        }

        $sub3 = 2_750_000 + 1_150_000 + 460_000 + 120_000 + 55_000;
        $tax3 = round($sub3 * 0.15, 2);
        $inv3 = Invoice::create([
            'purchase_order_id'      => $po3->id,
            'supplier_id'            => $sup['engineering']->id,
            'created_by'             => $admin->id,
            'supplier_invoice_number'=> 'AEC-INV-2026-0071',
            'invoice_date'           => now()->toDateString(),
            'due_date'               => now()->addDays(30)->toDateString(),
            'subtotal'               => $sub3,
            'tax_amount'             => $tax3,
            'total_amount'           => $sub3 + $tax3,
            'currency'               => 'ETB',
            'status'                 => Invoice::STATUS_MATCHED,
            'finance_status'         => 'Approved', 'finance_approved_by' => $admin->id, 'finance_approved_at' => now(),
            'director_status'        => 'Approved', 'director_approved_by'=> $admin->id, 'director_approved_at'=> now(),
            'notes'                  => 'RFP PROCUREMENT — Phase 1 milestone invoice (70% of contract value). 3-way match passed on delivered items.',
        ]);
        ThreeWayMatch::create(['invoice_id'=>$inv3->id,'purchase_order_id'=>$po3->id,'goods_receipt_id'=>$grn3->id,'match_status'=>'Matched','po_amount'=>$sub3+$tax3,'grn_amount'=>$sub3+$tax3,'invoice_amount'=>$sub3+$tax3,'variance'=>0,'matched_by'=>$admin->id,'matched_at'=>now()]);
        Payment::create(['invoice_id'=>$inv3->id,'supplier_id'=>$sup['engineering']->id,'created_by'=>$admin->id,'amount'=>$sub3+$tax3,'currency'=>'ETB','payment_method'=>'Bank Transfer','bank_name'=>'Awash Bank','scheduled_date'=>now()->addDays(15)->toDateString(),'status'=>'Pending Approval','finance_status'=>'Approved','finance_approved_by'=>$admin->id,'finance_approved_at'=>now(),'director_status'=>'Pending','notes'=>'RFP PAYMENT — Phase 1 milestone. Awaiting Director authorization.']);
        $this->command->info('     ✓ RFQ/RFP chain complete (PR→Formal RFP→3 Bids→Evaluation→PO→GRN→Invoice→Payment)');


        // ╔════════════════════════════════════════════════════════════╗
        // ║  TIER 4 — OPEN COMPETITION  (> ETB 38,500,000)           ║
        // ║  Scenario: ERP & Digital Infrastructure — ETB 52,000,000 ║
        // ║  Flow: PR → Published Tender → Bids → Evaluation → PO   ║
        // ╚════════════════════════════════════════════════════════════╝
        $this->command->info('  [4/4] Open Competition — ERP & Digital Infrastructure (ETB 52,000,000)...');

        $r4 = Requisition::create(array_merge($base, [
            'budget_id'          => $bud['ICT']->id,
            'requested_by'       => $uid(),
            'department'         => 'ICT Department',
            'cost_center'        => 'CC-ICT-003',
            'budget_code'        => $bud['ICT']->code,
            'category'           => 'Services',
            'procurement_method' => 'Open Competition',
            'required_by_date'   => now()->addDays(90)->toDateString(),
            'delivery_location'  => 'JSI Ethiopia Country Office, Addis Ababa',
            'justification'      => 'Enterprise-wide ERP system implementation and digital infrastructure upgrade for JSI Ethiopia, covering HR, Finance, Procurement, Fleet, M&E and Reporting modules. Total investment ETB 52,000,000 — requires full Open Competition with published solicitation, public advertisement, and BAFO process per JSI procurement rules (> ETB 38,500,000).',
            'estimated_total'    => 52_000_000,
            'overall_status'     => Requisition::STATUS_APPROVED,
            'supervisor_status'  => 'Approved', 'supervisor_approved_by' => $admin->id, 'supervisor_approved_at' => now()->subDays(35),
            'dept_head_status'   => 'Approved', 'dept_head_approved_by'  => $admin->id, 'dept_head_approved_at'  => now()->subDays(32),
            'finance_status'     => 'Approved', 'finance_approved_by'    => $admin->id, 'finance_approved_at'    => now()->subDays(28),
            'procurement_status' => 'Approved', 'procurement_approved_by'=> $admin->id, 'procurement_approved_at'=> now()->subDays(25),
        ]));
        foreach ([
            ['ERP Software Licences (Unlimited Users — Multi-Module)',    1, 'System', 18_000_000, 18_000_000],
            ['ERP Implementation, Configuration & Data Migration',        1, 'Service',14_000_000, 14_000_000],
            ['Integration Services (API, HRIMS, Finance, Fleet)',         1, 'Service', 5_000_000,  5_000_000],
            ['Server Infrastructure & Cloud Migration',                   1, 'Lot',     4_500_000,  4_500_000],
            ['Network Equipment — Core Switches, Firewalls, SD-WAN',     1, 'Lot',     3_200_000,  3_200_000],
            ['End-User Devices (120 laptops, 40 workstations)',         160, 'Unit',      32_500,  5_200_000],
            ['User Training — 5 Cohorts x 40 Participants',               5, 'Cohort',   240_000,  1_200_000],
            ['5-Year Support & Maintenance Contract',                     1, 'Service',   900_000,    900_000],
        ] as [$d,$q,$u,$up,$tp]) {
            RequisitionItem::create(['requisition_id'=>$r4->id,'description'=>$d,'quantity'=>$q,'unit'=>$u,'estimated_unit_price'=>$up,'estimated_total'=>$tp]);
        }
        $this->seedApprovalRecords($r4, 'requisition', $admin, 'Approved');

        $t4 = Tender::create([
            'requisition_id'      => $r4->id,
            'title'               => 'OPEN TENDER — Enterprise ERP System Implementation & Digital Infrastructure Upgrade for JSI Ethiopia',
            'description'         => 'JSI Ethiopia invites bids from qualified ERP solution providers and systems integrators for the supply, implementation, and 5-year support of an enterprise-wide ERP system. Scope includes HR, Finance, Procurement, Inventory, Asset, Fleet, M&E and Reporting modules, plus full digital infrastructure upgrade. This is a full Open Competition procurement — all qualified vendors are invited to participate. Pre-qualification documents required.',
            'method'              => 'Open Tender',
            'status'              => 'Evaluation',
            'visibility'          => 'public',
            'issue_date'          => now()->subDays(30)->toDateString(),
            'submission_deadline' => now()->subDays(2)->toDateString(),
            'opening_date'        => now()->subDays(1)->toDateString(),
            'award_date'          => now()->addDays(21)->toDateString(),
            'estimated_value'    => 52_000_000,
            'currency'            => 'ETB',
            'created_by'          => $admin->id,
            'terms_and_conditions'=> 'Pre-qualification required. Bid security: 2% of bid value (bank guarantee). Technical proposal — 70 points. Financial proposal — 30 points. Minimum technical pass score: 65/70. BAFO reserved if top two bids within 10% of each other. Bidders must demonstrate 3+ similar implementations > USD 500,000 in sub-Saharan Africa.',
        ]);

        // 4 bids received — evaluation in progress
        Bid::create(['tender_id'=>$t4->id,'supplier_id'=>$sup['it']->id,'reference_number'=>'HAIT-OPEN-2026-001','bid_amount'=>49_800_000,'currency'=>'ETB','submission_date'=>now()->subDays(3)->toDateString(),'validity_date'=>now()->addDays(90)->toDateString(),'status'=>'Shortlisted','technical_score'=>88,'financial_score'=>0,'composite_score'=>0,'delivery_days'=>180,'notes'=>'Strong technical proposal. 4 references in East Africa. Odoo ERP — prior JSI Nairobi implementation referenced.']);
        Bid::create(['tender_id'=>$t4->id,'supplier_id'=>$sup['engineering']->id,'reference_number'=>'AEC-OPEN-2026-003','bid_amount'=>51_200_000,'currency'=>'ETB','submission_date'=>now()->subDays(3)->toDateString(),'validity_date'=>now()->addDays(90)->toDateString(),'status'=>'Under Review','technical_score'=>82,'financial_score'=>0,'composite_score'=>0,'delivery_days'=>210,'notes'=>'SAP Business One proposal. Strong financial standing. Technical evaluation ongoing.']);
        Bid::create(['tender_id'=>$t4->id,'supplier_id'=>$sup['general']->id,'reference_number'=>'EBG-OPEN-2026-007','bid_amount'=>53_500_000,'currency'=>'ETB','submission_date'=>now()->subDays(3)->toDateString(),'validity_date'=>now()->addDays(90)->toDateString(),'status'=>'Under Review','technical_score'=>75,'financial_score'=>0,'composite_score'=>0,'delivery_days'=>240,'notes'=>'Microsoft Dynamics 365 proposal. Highest price. Evaluation in progress.']);
        Bid::create(['tender_id'=>$t4->id,'supplier_id'=>$sup['stationery']->id,'reference_number'=>'NOS-OPEN-2026-002','bid_amount'=>47_900_000,'currency'=>'ETB','submission_date'=>now()->subDays(3)->toDateString(),'validity_date'=>now()->addDays(90)->toDateString(),'status'=>'Under Review','technical_score'=>69,'financial_score'=>0,'composite_score'=>0,'delivery_days'=>200,'notes'=>'Lowest price but weaker technical score. Minimum references met.']);

        // PO in Draft — not yet awarded (evaluation ongoing)
        $po4 = PurchaseOrder::create([
            'supplier_id'      => $sup['it']->id,   // leading candidate
            'requisition_id'   => $r4->id,
            'tender_id'        => $t4->id,
            'created_by'       => $admin->id,
            'order_date'       => now()->addDays(22)->toDateString(),
            'delivery_date'    => now()->addDays(202)->toDateString(),
            'delivery_location'=> 'JSI Ethiopia Country Office, Addis Ababa',
            'payment_terms'    => '20% Advance · 40% Phase 1 Go-Live · 30% Phase 2 · 10% Final Acceptance',
            'currency'         => 'ETB',
            'notes'            => 'OPEN COMPETITION — 4 bids received. Technical evaluation in progress. Estimated award date: ' . now()->addDays(21)->format('d M Y') . '. PO pre-prepared for leading candidate per procurement best practices.',
            'overall_status'   => PurchaseOrder::STATUS_DRAFT,
        ]);
        foreach ([
            ['ERP Software Licences (Odoo Enterprise — Unlimited Users)', 1, 'System', 17_500_000],
            ['ERP Implementation & Data Migration (24 months)',            1, 'Service',13_500_000],
            ['API Integration Services (HR, Finance, Fleet, M&E)',        1, 'Service', 4_800_000],
            ['Cloud Infrastructure (Azure — 3yr prepaid reserve)',         1, 'Lot',     4_200_000],
            ['Network Equipment (Cisco SD-WAN, Fortinet Firewall)',        1, 'Lot',     3_100_000],
            ['End-User Devices (120 Dell Laptops, 40 HP Workstations)', 160, 'Unit',     31_500],
            ['Training — Admin, Finance, HR, Procurement, Exec (5 cohorts)',5,'Cohort',  220_000],
            ['5-Year Odoo Enterprise Support & Maintenance SLA',          1, 'Service',   900_000],
        ] as [$d,$q,$u,$up]) {
            PurchaseOrderItem::create(['purchase_order_id'=>$po4->id,'description'=>$d,'quantity'=>$q,'unit'=>$u,'unit_price'=>$up,'total_price'=>$q*$up]);
        }
        $this->command->info('     ✓ Open Competition chain complete (PR→Published Tender→4 Bids→Evaluation→PO Draft pending award)');

        // ── Summary ──────────────────────────────────────────────────
        $this->command->info('');
        $this->command->info('  ╔══════════════════════════════════════════════════════════╗');
        $this->command->info('  ║   JSI Demo Data Seeded Successfully!                    ║');
        $this->command->info('  ║                                                          ║');
        $this->command->info('  ║  Tier 1 Micro Purchase       ETB 44,361   ✓ PAID         ║');
        $this->command->info('  ║  Tier 2 Simplified           ETB ~320K    ✓ SCHEDULED    ║');
        $this->command->info('  ║  Tier 3 RFQ/RFP              ETB 4.8M     ⏳ PENDING DIR ║');
        $this->command->info('  ║  Tier 4 Open Competition     ETB 52M      📋 EVALUATION  ║');
        $this->command->info('  ╚══════════════════════════════════════════════════════════╝');
    }

    // ── Helpers ──────────────────────────────────────────────────────

    private function seedApprovalRecords(object $model, string $docType, User $admin, string $status): void
    {
        $workflow = ProcurementApprovalWorkflow::activeFor($docType);
        if (! $workflow) return;

        foreach ($workflow->stages as $stage) {
            ProcurementApprovalRecord::firstOrCreate(
                [
                    'approvable_type' => $model::class,
                    'approvable_id'   => $model->getKey(),
                    'stage_order'     => $stage->stage_order,
                ],
                [
                    'stage_id'      => $stage->id,
                    'stage_name'    => $stage->stage_name,
                    'required_role' => $stage->required_role,
                    'status'        => $status,
                    'decided_by'    => $status === 'Approved' ? $admin->id : null,
                    'decided_at'    => $status === 'Approved' ? now() : null,
                    'notes'         => $status === 'Approved' ? 'Approved by JSI Demo Seeder' : null,
                ]
            );
        }
    }

    private function ensureSuppliers(): array
    {
        $rows = [
            'stationery' => ['National Office Supplies PLC', 'JSI-SUP-NOS', 'orders@nationaloffice.et', '+251934560001', 'Piassa, Addis Ababa', '0056789012', 'Dashen Bank', '0151234560001', 'Goods', 'Biruk Assefa'],
            'medical'    => ['Addis Ababa Medical Supplies Enterprise', 'JSI-SUP-AAMS', 'procurement@aamse.et', '+251911230004', 'Yeka Sub-City, Addis Ababa', '0067890123', 'Commercial Bank of Ethiopia', '1000775432100', 'Goods', 'Hanna Tesfaye'],
            'engineering'=> ['Abay Engineering & Consultancy PLC', 'JSI-SUP-AEC', 'info@abayconsult.et', '+251912300056', 'Gullele Sub-City, Addis Ababa', '0089012345', 'Awash Bank', '01320099001234', 'Services', 'Mekdes Alemu'],
            'it'         => ['Horn of Africa IT Solutions PLC', 'JSI-SUP-HAIT', 'sales@hoait.et', '+251912345670', 'Kirkos Sub-City, Addis Ababa', '0034567890', 'Bank of Abyssinia', '78901234563', 'Services', 'Yonas Bekele'],
            'general'    => ['Ethio Business Group PLC', 'JSI-SUP-EBG', 'info@ethiobusiness.et', '+251911234567', 'Bole Sub-City, Addis Ababa', '0012345678', 'Commercial Bank of Ethiopia', '1000452789012', 'Goods', 'Dawit Tadesse'],
        ];

        $result = [];
        foreach ($rows as $key => [$name,$code,$email,$phone,$addr,$tin,$bank,$acc,$cat,$cp]) {
            $result[$key] = Supplier::firstOrCreate(
                ['code' => $code],
                compact('name','email','phone') + ['address'=>$addr,'tin_number'=>$tin,'bank_name'=>$bank,'bank_account'=>$acc,'category'=>$cat,'contact_person'=>$cp,'status'=>'Active']
            );
        }
        return $result;
    }

    private function ensureBudgets(string $yr): array
    {
        $rows = [
            'ADM'   => ["BUD-JSI-{$yr}-ADM",   'Administration & Office Operations', 'Administration',       'CC-ADM-001', 1_000_000,   80_000,  320_000],
            'MED'   => ["BUD-JSI-{$yr}-MED",   'Medical Supplies & Health Programs', 'Health & Safety',       'CC-HLT-001', 2_500_000,  200_000,  680_000],
            'INFRA' => ["BUD-JSI-{$yr}-INFRA", 'Infrastructure & Facilities',        'Facilities Management', 'CC-FAC-002',12_000_000,1_800_000,4_200_000],
            'ICT'   => ["BUD-JSI-{$yr}-ICT",   'ICT & Digital Transformation',       'ICT Department',        'CC-ICT-003',60_000_000,  300_000,        0],
        ];

        $result = [];
        foreach ($rows as $key => [$code,$title,$dept,$cc,$alloc,$committed,$expended]) {
            $result[$key] = ProcurementBudget::firstOrCreate(
                ['code' => $code],
                compact('title','code') + ['department'=>$dept,'cost_center'=>$cc,'fiscal_year'=>$yr,'allocated_amount'=>$alloc,'committed_amount'=>$committed,'expended_amount'=>$expended,'status'=>'Active']
            );
        }
        return $result;
    }
}

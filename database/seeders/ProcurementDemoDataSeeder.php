<?php

namespace Database\Seeders;

use App\Models\Procurement\Bid;
use App\Models\Procurement\Contract;
use App\Models\Procurement\ContractVersion;
use App\Models\Procurement\GoodsReceipt;
use App\Models\Procurement\GoodsReceiptItem;
use App\Models\Procurement\Invoice;
use App\Models\Procurement\Payment;
use App\Models\Procurement\ProcurementBudget;
use App\Models\Procurement\PurchaseOrder;
use App\Models\Procurement\PurchaseOrderItem;
use App\Models\Procurement\Requisition;
use App\Models\Procurement\RequisitionItem;
use App\Models\Procurement\Supplier;
use App\Models\Procurement\Tender;
use App\Models\Procurement\ThreeWayMatch;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProcurementDemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🛒 Seeding Ethiopian Procurement Demo Data...');

        $admin = User::first();
        if (!$admin) {
            $this->command->error('No users found — run user seeder first.');
            return;
        }
        $users = User::limit(5)->get();
        $uid   = fn () => $users->random()->id;

        // ══════════════════════════════════════════════════════════
        // 1. SUPPLIERS
        //    Actual cols: id, name, code, email, phone, address,
        //    tin_number, bank_name, bank_account, contact_person,
        //    category, status, notes, timestamps, softDeletes
        // ══════════════════════════════════════════════════════════
        $this->command->info('  Creating Suppliers...');

        $sup = [];
        foreach ([
            ['Horn of Africa IT Solutions PLC',         'SUP-HAIT', 'Yonas Bekele',    'sales@hoait.et',              '+251912345670', 'Kirkos Sub-City, Addis Ababa',       '0034567890', 'Bank of Abyssinia',         '78901234563',   'Services',   'Active'],
            ['Ethio Business Group PLC',                 'SUP-EBG',  'Dawit Tadesse',   'info@ethiobusiness.et',       '+251911234567', 'Bole Sub-City, Addis Ababa',         '0012345678', 'Commercial Bank of Ethiopia','1000452789012', 'Goods',      'Active'],
            ['Kombolcha Industrial Supplies Co.',         'SUP-KIS',  'Almaz Girma',     'supply@kombolchaindustrial.et','+251933456789','Kombolcha, Amhara Region',           '0023456789', 'Awash Bank',               '01320050000123','Works',      'Active'],
            ['Addis Construction Materials PLC',          'SUP-ACM',  'Tigist Haile',    'tigist@addisconstruction.et', '+251921234560', 'Akaki Kality, Addis Ababa',         '0045678901', 'Dashen Bank',              '0151234567890', 'Works',      'Active'],
            ['National Stationery & Print PLC',           'SUP-NSP',  'Biruk Assefa',    'orders@nationalstationery.et','+251934560123', 'Piassa, Addis Ababa',               '0056789012', 'Cooperative Bank of Oromia','1019876543210','Goods',      'Active'],
            ['Addis Ababa Medical Supplies Enterprise',   'SUP-AMSE', 'Hanna Tesfaye',   'procurement@aamse.et',        '+251911230004', 'Yeka Sub-City, Addis Ababa',        '0067890123', 'Commercial Bank of Ethiopia','1000775432100','Goods',      'Active'],
            ['Sheger Transport & Logistics PLC',          'SUP-STL',  'Solomon Mulugeta','ops@shegertransport.et',      '+251923450123', 'Nifas Silk-Lafto, Addis Ababa',     '0078901234', 'Zemen Bank',               '0011234567890', 'Services',   'Active'],
            ['Abay Engineering & Consultancy PLC',        'SUP-AEC',  'Mekdes Alemu',    'info@abayconsult.et',         '+251912300056', 'Gullele Sub-City, Addis Ababa',     '0089012345', 'Awash Bank',               '01320099001234','Consultancy','Inactive'],
        ] as [$name,$code,$cp,$email,$phone,$addr,$tin,$bank,$acc,$cat,$status]) {
            $sup[] = Supplier::create(['name'=>$name,'code'=>$code,'contact_person'=>$cp,'email'=>$email,'phone'=>$phone,'address'=>$addr,'tin_number'=>$tin,'bank_name'=>$bank,'bank_account'=>$acc,'category'=>$cat,'status'=>$status]);
        }

        // ══════════════════════════════════════════════════════════
        // 2. BUDGETS
        //    Actual cols: id, code, title, department, cost_center,
        //    fiscal_year, allocated_amount, committed_amount,
        //    expended_amount, status, notes, timestamps, softDeletes
        // ══════════════════════════════════════════════════════════
        $this->command->info('  Creating Budget Lines...');

        $yr = (string) now()->year;
        $bud = [];
        foreach ([
            ["BUD-{$yr}-ICT",   'ICT Equipment & Software',       'ICT Department',         'CC-ICT-001', 1_500_000, 380_000,   620_000],
            ["BUD-{$yr}-OPS",   'Office Operations & Consumables', 'Administration',          'CC-ADM-001',   500_000,  45_000,   210_000],
            ["BUD-{$yr}-CIVIL", 'Civil Works & Infrastructure',    'Facilities Management',   'CC-FAC-001', 8_000_000,1_200_000,3_400_000],
            ["BUD-{$yr}-MED",   'Medical Supplies & Equipment',    'Health & Safety',         'CC-HLT-001',   750_000, 120_000,   280_000],
            ["BUD-{$yr}-VEH",   'Vehicle & Fuel',                  'Logistics',               'CC-LOG-001',   900_000, 200_000,   450_000],
            ["BUD-{$yr}-CONS",  'Consultancy & Professional Svcs', 'Strategy & Planning',     'CC-STR-001', 2_000_000, 600_000,   800_000],
            ["BUD-{$yr}-STAT",  'Stationery & Printing',           'Administration',          'CC-ADM-002',   200_000,  15_000,    95_000],
        ] as [$code,$title,$dept,$cc,$alloc,$committed,$expended]) {
            $bud[] = ProcurementBudget::create([
                'code' => $code, 'title' => $title, 'department' => $dept,
                'cost_center' => $cc, 'fiscal_year' => $yr,
                'allocated_amount' => $alloc, 'committed_amount' => $committed,
                'expended_amount' => $expended, 'status' => 'Active',
            ]);
        }

        // ══════════════════════════════════════════════════════════
        // 3. REQUISITIONS
        //    Actual cols: requisition_number(auto), budget_id,
        //    requested_by, department, cost_center, budget_code,
        //    category, procurement_method, required_by_date,
        //    justification, delivery_location, estimated_total,
        //    overall_status, supervisor/dept_head/finance/procurement
        //    _status/_approved_by/_approved_at/_remarks, attachments
        // ══════════════════════════════════════════════════════════
        $this->command->info('  Creating Requisitions...');

        $req = [];
        $base = ['supervisor_status'=>'Pending','dept_head_status'=>'Pending','finance_status'=>'Pending','procurement_status'=>'Pending'];

        // REQ 1 — Fully Approved (laptops)
        $req[] = Requisition::create(array_merge($base, [
            'budget_id'=>$bud[0]->id,'requested_by'=>$uid(),
            'department'=>'ICT Department','cost_center'=>'CC-ICT-001','budget_code'=>$bud[0]->code,
            'category'=>'Goods','procurement_method'=>'Open Tender','estimated_total'=>420_000,
            'required_by_date'=>now()->addDays(30)->toDateString(),
            'delivery_location'=>'ICT Department, HQ Building, Addis Ababa',
            'justification'=>'Eight new engineers joining the ICT department require laptop computers and peripherals.',
            'overall_status'=>Requisition::STATUS_APPROVED,
            'supervisor_status'=>'Approved','supervisor_approved_by'=>$admin->id,'supervisor_approved_at'=>now()->subDays(20),
            'dept_head_status'=>'Approved','dept_head_approved_by'=>$admin->id,'dept_head_approved_at'=>now()->subDays(18),
            'finance_status'=>'Approved','finance_approved_by'=>$admin->id,'finance_approved_at'=>now()->subDays(15),
            'procurement_status'=>'Approved','procurement_approved_by'=>$admin->id,'procurement_approved_at'=>now()->subDays(12),
        ]));
        foreach ([
            ['Dell Latitude 5540 Laptop (i7, 16GB RAM, 512GB SSD)', 8, 'Unit', 45_000],
            ['Laptop Bag 15.6"', 8, 'Unit', 650],
            ['Wireless Mouse & Keyboard Set', 8, 'Set', 1_200],
        ] as [$desc,$qty,$unit,$price]) {
            RequisitionItem::create(['requisition_id'=>$req[0]->id,'description'=>$desc,'quantity'=>$qty,'unit'=>$unit,'estimated_unit_price'=>$price,'estimated_total'=>$qty*$price]);
        }

        // REQ 2 — Fully Approved (stationery)
        $req[] = Requisition::create(array_merge($base, [
            'budget_id'=>$bud[6]->id,'requested_by'=>$uid(),
            'department'=>'Administration','cost_center'=>'CC-ADM-001','budget_code'=>$bud[6]->code,
            'category'=>'Goods','procurement_method'=>'RFQ','estimated_total'=>55_000,
            'required_by_date'=>now()->addDays(14)->toDateString(),
            'delivery_location'=>'Stores, Ground Floor, HQ Building, Addis Ababa',
            'justification'=>'Quarterly replenishment of stationery for all departments.',
            'overall_status'=>Requisition::STATUS_APPROVED,
            'supervisor_status'=>'Approved','supervisor_approved_by'=>$admin->id,'supervisor_approved_at'=>now()->subDays(10),
            'dept_head_status'=>'Approved','dept_head_approved_by'=>$admin->id,'dept_head_approved_at'=>now()->subDays(8),
            'finance_status'=>'Approved','finance_approved_by'=>$admin->id,'finance_approved_at'=>now()->subDays(6),
            'procurement_status'=>'Approved','procurement_approved_by'=>$admin->id,'procurement_approved_at'=>now()->subDays(4),
        ]));
        foreach ([
            ['A4 Paper (80gsm) — Box of 5 Reams', 50, 'Box', 550],
            ['Ballpoint Pens — Box of 50', 30, 'Box', 180],
            ['HP LaserJet Toner Cartridge CF217A', 10, 'Unit', 2_800],
            ['Register Books A4 96-Page', 40, 'Unit', 45],
        ] as [$desc,$qty,$unit,$price]) {
            RequisitionItem::create(['requisition_id'=>$req[1]->id,'description'=>$desc,'quantity'=>$qty,'unit'=>$unit,'estimated_unit_price'=>$price,'estimated_total'=>$qty*$price]);
        }

        // REQ 3 — Submitted (generator maintenance)
        $req[] = Requisition::create(array_merge($base, [
            'budget_id'=>$bud[1]->id,'requested_by'=>$uid(),
            'department'=>'Facilities Management','cost_center'=>'CC-FAC-001','budget_code'=>$bud[1]->code,
            'category'=>'Services','procurement_method'=>'RFQ','estimated_total'=>120_000,
            'required_by_date'=>now()->addDays(7)->toDateString(),
            'delivery_location'=>'Generator Room, HQ Building, Addis Ababa',
            'justification'=>'Annual preventive maintenance for two 150KVA generators overdue. Risk of power disruption.',
            'overall_status'=>Requisition::STATUS_SUBMITTED,
            'supervisor_status'=>'Approved','supervisor_approved_by'=>$admin->id,'supervisor_approved_at'=>now()->subDays(3),
        ]));
        foreach ([
            ['Generator Servicing 150KVA — Annual PM', 2, 'Unit', 45_000],
            ['Engine Oil (15W-40) — 20 Litre Drum', 4, 'Drum', 3_500],
            ['Air Filter Replacement', 2, 'Unit', 4_800],
        ] as [$desc,$qty,$unit,$price]) {
            RequisitionItem::create(['requisition_id'=>$req[2]->id,'description'=>$desc,'quantity'=>$qty,'unit'=>$unit,'estimated_unit_price'=>$price,'estimated_total'=>$qty*$price]);
        }

        // REQ 4 — Draft (network infrastructure)
        $req[] = Requisition::create(array_merge($base, [
            'budget_id'=>$bud[0]->id,'requested_by'=>$uid(),
            'department'=>'ICT Department','cost_center'=>'CC-ICT-002','budget_code'=>$bud[0]->code,
            'category'=>'Works','procurement_method'=>'Open Tender','estimated_total'=>850_000,
            'required_by_date'=>now()->addDays(60)->toDateString(),
            'delivery_location'=>'New Office Block, Annexe Building, Addis Ababa',
            'justification'=>'Structured cabling, switches and Wi-Fi APs for new office block.',
            'overall_status'=>Requisition::STATUS_DRAFT,
        ]));

        // ══════════════════════════════════════════════════════════
        // 4. TENDERS
        //    Actual cols: tender_number(auto), requisition_id, title,
        //    description, method, status, issue_date, submission_deadline,
        //    opening_date, award_date, estimated_value, currency,
        //    evaluation_criteria, terms_and_conditions, attachments, created_by
        // ══════════════════════════════════════════════════════════
        $this->command->info('  Creating Tenders...');

        $ten = [];
        $ten[] = Tender::create(['title'=>'Supply of Laptop Computers and Peripherals','description'=>'Open tender for supply of 8 laptops per approved requisition.','status'=>'Awarded','method'=>'Open Tender','estimated_value'=>420_000,'submission_deadline'=>now()->subDays(25)->toDateString(),'opening_date'=>now()->subDays(24)->toDateString(),'award_date'=>now()->subDays(12)->toDateString(),'created_by'=>$admin->id,'requisition_id'=>$req[0]->id,'currency'=>'ETB']);
        $ten[] = Tender::create(['title'=>'Annual Generator Preventive Maintenance Contract','description'=>'RFQ for annual PM of two 150KVA generators at HQ.','status'=>'Published','method'=>'RFQ','estimated_value'=>130_000,'submission_deadline'=>now()->addDays(10)->toDateString(),'opening_date'=>now()->addDays(11)->toDateString(),'created_by'=>$admin->id,'requisition_id'=>$req[2]->id,'currency'=>'ETB']);
        $ten[] = Tender::create(['title'=>'ICT Network Infrastructure Installation Works','description'=>'Open tender for structured cabling and networking equipment.','status'=>'Evaluation','method'=>'Open Tender','estimated_value'=>850_000,'submission_deadline'=>now()->subDays(5)->toDateString(),'opening_date'=>now()->subDays(4)->toDateString(),'created_by'=>$admin->id,'requisition_id'=>$req[3]->id,'currency'=>'ETB']);
        $ten[] = Tender::create(['title'=>'ERP System Consultancy Services','description'=>'RFP for professional consultancy to support ERP implementation.','status'=>'Closed','method'=>'RFP','estimated_value'=>1_200_000,'submission_deadline'=>now()->subDays(8)->toDateString(),'opening_date'=>now()->subDays(7)->toDateString(),'created_by'=>$admin->id,'currency'=>'ETB']);

        // ══════════════════════════════════════════════════════════
        // 5. BIDS
        //    Actual cols: tender_id, supplier_id, reference_number,
        //    submission_date, bid_amount, currency, delivery_days,
        //    status, technical_score, financial_score, composite_score,
        //    validity_date, notes, attachments, conflict_of_interest_declared
        //    NOTE: unique constraint on (tender_id, supplier_id)
        // ══════════════════════════════════════════════════════════
        $this->command->info('  Creating Bids...');

        $bid1 = Bid::create(['tender_id'=>$ten[0]->id,'supplier_id'=>$sup[0]->id,'reference_number'=>'HAIT-BID-001','bid_amount'=>415_000,'currency'=>'ETB','submission_date'=>now()->subDays(26)->toDateString(),'validity_date'=>now()->addDays(60)->toDateString(),'status'=>'Awarded','technical_score'=>92,'financial_score'=>88,'composite_score'=>90,'delivery_days'=>14]);
                  Bid::create(['tender_id'=>$ten[0]->id,'supplier_id'=>$sup[1]->id,'reference_number'=>'EBG-BID-001', 'bid_amount'=>438_000,'currency'=>'ETB','submission_date'=>now()->subDays(26)->toDateString(),'validity_date'=>now()->addDays(60)->toDateString(),'status'=>'Rejected','technical_score'=>78,'financial_score'=>72,'composite_score'=>75,'delivery_days'=>21]);
                  Bid::create(['tender_id'=>$ten[0]->id,'supplier_id'=>$sup[4]->id,'reference_number'=>'NSP-BID-001', 'bid_amount'=>450_000,'currency'=>'ETB','submission_date'=>now()->subDays(26)->toDateString(),'validity_date'=>now()->addDays(60)->toDateString(),'status'=>'Rejected','technical_score'=>65,'financial_score'=>70,'composite_score'=>68,'delivery_days'=>30]);
                  Bid::create(['tender_id'=>$ten[2]->id,'supplier_id'=>$sup[0]->id,'reference_number'=>'HAIT-BID-002','bid_amount'=>820_000,'currency'=>'ETB','submission_date'=>now()->subDays(6)->toDateString(), 'validity_date'=>now()->addDays(60)->toDateString(),'status'=>'Shortlisted','technical_score'=>88,'financial_score'=>91,'composite_score'=>90,'delivery_days'=>45]);
                  Bid::create(['tender_id'=>$ten[2]->id,'supplier_id'=>$sup[2]->id,'reference_number'=>'KIS-BID-001', 'bid_amount'=>798_000,'currency'=>'ETB','submission_date'=>now()->subDays(6)->toDateString(), 'validity_date'=>now()->addDays(60)->toDateString(),'status'=>'Under Review','technical_score'=>75,'financial_score'=>85,'composite_score'=>80,'delivery_days'=>60]);
                  Bid::create(['tender_id'=>$ten[3]->id,'supplier_id'=>$sup[7]->id,'reference_number'=>'AEC-BID-001', 'bid_amount'=>1_150_000,'currency'=>'ETB','submission_date'=>now()->subDays(9)->toDateString(),'validity_date'=>now()->addDays(45)->toDateString(),'status'=>'Submitted','delivery_days'=>90]);
                  Bid::create(['tender_id'=>$ten[3]->id,'supplier_id'=>$sup[0]->id,'reference_number'=>'HAIT-BID-003','bid_amount'=>980_000,'currency'=>'ETB','submission_date'=>now()->subDays(9)->toDateString(), 'validity_date'=>now()->addDays(45)->toDateString(),'status'=>'Submitted','delivery_days'=>75]);

        // ══════════════════════════════════════════════════════════
        // 6. PURCHASE ORDERS
        //    Required: order_date, delivery_date, supplier_id, created_by
        // ══════════════════════════════════════════════════════════
        $this->command->info('  Creating Purchase Orders...');

        $po1 = PurchaseOrder::create([
            'supplier_id'=>$sup[0]->id,'tender_id'=>$ten[0]->id,'requisition_id'=>$req[0]->id,'bid_id'=>$bid1->id,'created_by'=>$admin->id,
            'order_date'=>now()->subDays(10)->toDateString(),'delivery_date'=>now()->addDays(14)->toDateString(),
            'delivery_location'=>'ICT Department, HQ Building, Addis Ababa',
            'payment_terms'=>'Net 30 from accepted Goods Receipt Note',
            'currency'=>'ETB','subtotal'=>376_600,'tax_rate'=>15,'tax_amount'=>56_490,'total_amount'=>433_090,
            'overall_status'=>PurchaseOrder::STATUS_SENT,
            'procurement_officer_status'=>'Approved','procurement_officer_approved_by'=>$admin->id,'procurement_officer_approved_at'=>now()->subDays(11),
            'finance_status'=>'Approved','finance_approved_by'=>$admin->id,'finance_approved_at'=>now()->subDays(9),
            'director_status'=>'Approved','director_approved_by'=>$admin->id,'director_approved_at'=>now()->subDays(7),
            'notes'=>'Warranty: 2 years on all units. Delivery in original sealed boxes with serial numbers.',
        ]);
        $poItems1 = [];
        foreach ([
            ['Dell Latitude 5540 Laptop (i7, 16GB RAM, 512GB SSD)', 8, 'Unit', 45_000, 360_000],
            ['Laptop Bag 15.6"', 8, 'Unit', 650, 5_200],
            ['Wireless Mouse & Keyboard Set', 8, 'Set', 1_200, 9_600],
            ['Antivirus Licence — 1 Year (per seat)', 8, 'Lic', 225, 1_800],
        ] as [$desc,$qty,$unit,$up,$tp]) {
            $poItems1[] = PurchaseOrderItem::create(['purchase_order_id'=>$po1->id,'description'=>$desc,'quantity'=>$qty,'unit'=>$unit,'unit_price'=>$up,'total_price'=>$tp]);
        }

        $po2 = PurchaseOrder::create([
            'supplier_id'=>$sup[4]->id,'requisition_id'=>$req[1]->id,'created_by'=>$admin->id,
            'order_date'=>now()->subDays(3)->toDateString(),'delivery_date'=>now()->addDays(5)->toDateString(),
            'delivery_location'=>'Stores, Ground Floor, HQ Building, Addis Ababa',
            'payment_terms'=>'Payment on delivery and GRN acceptance',
            'currency'=>'ETB','subtotal'=>48_950,'tax_rate'=>15,'tax_amount'=>7_342.50,'total_amount'=>56_292.50,
            'overall_status'=>PurchaseOrder::STATUS_APPROVED,
            'procurement_officer_status'=>'Approved','procurement_officer_approved_by'=>$admin->id,'procurement_officer_approved_at'=>now()->subDays(3),
            'finance_status'=>'Approved','finance_approved_by'=>$admin->id,'finance_approved_at'=>now()->subDays(2),
            'director_status'=>'Pending',
        ]);

        // ══════════════════════════════════════════════════════════
        // 7. GOODS RECEIPTS
        //    Required: purchase_order_id, received_by, receipt_date
        //    GRN items require po_item_id (FK NOT NULL)
        // ══════════════════════════════════════════════════════════
        $this->command->info('  Creating GRNs...');

        $grn1 = GoodsReceipt::create([
            'purchase_order_id'=>$po1->id,
            'received_by'=>$admin->id,
            'receipt_date'=>now()->subDays(2)->toDateString(),
            'delivery_location'=>'ICT Department, HQ Building, Addis Ababa',
            'delivery_note_number'=>'DN-HAIT-2026-0451',
            'overall_condition'=>'Good',
            'status'=>'Accepted',
            'inspection_notes'=>'All 8 Dell laptops in original sealed boxes. S/N verified. Accessories complete.',
            'inspected_by'=>$admin->id,'inspected_at'=>now()->subDays(1),
            'approved_by'=>$admin->id,'approved_at'=>now()->subDays(1),
        ]);
        // Link GRN items to actual PO items
        foreach ([0,1,2] as $i) {
            GoodsReceiptItem::create([
                'goods_receipt_id'=>$grn1->id,
                'po_item_id'=>$poItems1[$i]->id,
                'received_quantity'=>$poItems1[$i]->quantity,
                'accepted_quantity'=>$poItems1[$i]->quantity,
                'rejected_quantity'=>0,
                'condition'=>'Pass',
                'inspection_remarks'=>'Received in good condition',
            ]);
        }

        // ══════════════════════════════════════════════════════════
        // 8. INVOICES
        //    Required: purchase_order_id, supplier_id, created_by,
        //    invoice_date, due_date
        // ══════════════════════════════════════════════════════════
        $this->command->info('  Creating Invoices...');

        $inv1 = Invoice::create([
            'purchase_order_id'=>$po1->id,'supplier_id'=>$sup[0]->id,'created_by'=>$admin->id,
            'supplier_invoice_number'=>'HAIT-INV-2026-0089',
            'invoice_date'=>now()->subDays(1)->toDateString(),
            'due_date'=>now()->addDays(29)->toDateString(),
            'subtotal'=>376_600,'tax_amount'=>56_490,'total_amount'=>433_090,'currency'=>'ETB',
            'status'=>Invoice::STATUS_MATCHED,
            'finance_status'=>'Pending','director_status'=>'Pending',
            'notes'=>'3-way match passed. Invoice ready for Finance authorization.',
        ]);

        // Overdue invoice for dashboard alert
        $inv2 = Invoice::create([
            'purchase_order_id'=>$po2->id,'supplier_id'=>$sup[4]->id,'created_by'=>$admin->id,
            'supplier_invoice_number'=>'NSP-INV-2026-0043',
            'invoice_date'=>now()->subDays(45)->toDateString(),
            'due_date'=>now()->subDays(15)->toDateString(),   // ← OVERDUE
            'subtotal'=>48_950,'tax_amount'=>7_342.50,'total_amount'=>56_292.50,'currency'=>'ETB',
            'status'=>Invoice::STATUS_SUBMITTED,
            'finance_status'=>'Pending','director_status'=>'Pending',
            'notes'=>'Q1 stationery invoice — awaiting Finance review.',
        ]);

        // ══════════════════════════════════════════════════════════
        // 9. THREE-WAY MATCH
        // ══════════════════════════════════════════════════════════
        $this->command->info('  Creating 3-Way Match...');

        ThreeWayMatch::create([
            'invoice_id'=>$inv1->id,'purchase_order_id'=>$po1->id,'goods_receipt_id'=>$grn1->id,
            'match_status'=>'Matched','po_amount'=>433_090,'grn_amount'=>433_090,
            'invoice_amount'=>433_090,'variance'=>0,
            'matched_by'=>$admin->id,'matched_at'=>now(),
        ]);

        // ══════════════════════════════════════════════════════════
        // 10. PAYMENTS
        //     Note: invoice_id is NOT NULL (required)
        // ══════════════════════════════════════════════════════════
        $this->command->info('  Creating Payments...');

        // Scheduled payment for laptop invoice
        Payment::create([
            'invoice_id'=>$inv1->id,'supplier_id'=>$sup[0]->id,'created_by'=>$admin->id,
            'amount'=>433_090,'currency'=>'ETB','payment_method'=>'Bank Transfer',
            'bank_name'=>'Commercial Bank of Ethiopia',
            'scheduled_date'=>now()->addDays(7)->toDateString(),
            'status'=>'Scheduled','finance_status'=>'Pending','director_status'=>'Pending',
            'notes'=>'Payment for laptop supply — Invoice HAIT-INV-2026-0089',
        ]);

        // Pending Director auth — stationery invoice
        Payment::create([
            'invoice_id'=>$inv2->id,'supplier_id'=>$sup[4]->id,'created_by'=>$admin->id,
            'amount'=>56_292.50,'currency'=>'ETB','payment_method'=>'Cheque',
            'bank_name'=>'Cooperative Bank of Oromia',
            'scheduled_date'=>now()->addDays(2)->toDateString(),
            'status'=>'Pending Approval',
            'finance_status'=>'Approved','finance_approved_by'=>$admin->id,'finance_approved_at'=>now()->subDays(1),
            'director_status'=>'Pending',
            'notes'=>'Q1 stationery invoice payment — pending Director sign-off',
        ]);

        // ══════════════════════════════════════════════════════════
        // 11. CONTRACTS
        // ══════════════════════════════════════════════════════════
        $this->command->info('  Creating Contracts...');

        $c1 = Contract::create([
            'supplier_id'=>$sup[0]->id,'tender_id'=>$ten[0]->id,'bid_id'=>$bid1->id,'purchase_order_id'=>$po1->id,
            'created_by'=>$admin->id,
            'title'=>'Supply of ICT Equipment — Dell Laptops & Accessories',
            'contract_type'=>'Goods Supply','status'=>'Active',
            'effective_date'=>now()->subDays(10)->toDateString(),
            'expiry_date'=>now()->addDays(355)->toDateString(),
            'supplier_signed_at'=>now()->subDays(12)->toDateString(),
            'org_signed_at'=>now()->subDays(10)->toDateString(),
            'currency'=>'ETB','contract_value'=>433_090,
            'advance_payment_percentage'=>0,'payment_terms'=>'Net 30 from accepted GRN',
            'org_signatory_name'=>'Ato Tesfaye Kebede','org_signatory_title'=>'Procurement Director',
            'supplier_contact_person'=>'Yonas Bekele',
            'approval_status'=>'Approved','approved_by'=>$admin->id,'approved_at'=>now()->subDays(10),
            'special_conditions'=>'Warranty: 2 years on all laptop units. Defective units replaced within 7 working days.',
        ]);

        // Framework contract — expiring soon (for dashboard alert)
        Contract::create([
            'supplier_id'=>$sup[6]->id,'created_by'=>$admin->id,
            'title'=>'Courier & Freight Services Framework Agreement',
            'contract_type'=>'Framework','status'=>'Active',
            'effective_date'=>now()->subMonths(6)->toDateString(),
            'expiry_date'=>now()->addDays(22)->toDateString(),       // ← expiring in 22 days!
            'supplier_signed_at'=>now()->subMonths(6)->subDays(2)->toDateString(),
            'org_signed_at'=>now()->subMonths(6)->toDateString(),
            'currency'=>'ETB','contract_value'=>900_000,
            'payment_terms'=>'Monthly invoice, paid within 15 days',
            'org_signatory_name'=>'Ato Tesfaye Kebede','org_signatory_title'=>'Procurement Director',
            'supplier_contact_person'=>'Solomon Mulugeta',
            'approval_status'=>'Approved','approved_by'=>$admin->id,'approved_at'=>now()->subMonths(6),
            'special_conditions'=>'Max rate ETB 25/km intercity. Addis CBD flat rate ETB 350 per delivery.',
        ]);

        Contract::create([
            'supplier_id'=>$sup[2]->id,'created_by'=>$admin->id,
            'title'=>'Supply and Maintenance of Industrial Equipment',
            'contract_type'=>'Goods Supply','status'=>'Pending Signature',
            'effective_date'=>now()->addDays(5)->toDateString(),
            'expiry_date'=>now()->addMonths(12)->toDateString(),
            'currency'=>'ETB','contract_value'=>650_000,
            'advance_payment_percentage'=>20,'payment_terms'=>'20% advance, 80% on delivery & GRN',
            'org_signatory_name'=>'Ato Tesfaye Kebede','org_signatory_title'=>'Procurement Director',
            'supplier_contact_person'=>'Almaz Girma',
            'approval_status'=>'Approved','approved_by'=>$admin->id,'approved_at'=>now()->subDays(2),
        ]);

        // Contract amendment
        ContractVersion::create([
            'contract_id'=>$c1->id,'version_number'=>1,
            'change_summary'=>'Added 2 antivirus licences for newly approved positions. Value increased by ETB 450.',
            'amended_value'=>433_540,'amendment_date'=>now()->subDays(5)->toDateString(),
            'amended_by'=>$admin->id,
        ]);

        $this->command->info('');
        $this->command->info('  Ethiopian Procurement Demo Data seeded successfully!');
        $this->command->info('   ✓ ' . Supplier::count()          . ' Suppliers');
        $this->command->info('   ✓ ' . ProcurementBudget::count() . ' Budget Lines');
        $this->command->info('   ✓ ' . Requisition::count()       . ' Requisitions & Items');
        $this->command->info('   ✓ ' . Tender::count()            . ' Tenders');
        $this->command->info('   ✓ ' . Bid::count()               . ' Bids');
        $this->command->info('   ✓ ' . PurchaseOrder::count()     . ' Purchase Orders');
        $this->command->info('   ✓ ' . GoodsReceipt::count()      . ' GRNs');
        $this->command->info('   ✓ ' . Invoice::count()           . ' Invoices (1 overdue)');
        $this->command->info('   ✓ ' . ThreeWayMatch::count()     . ' 3-Way Match Records');
        $this->command->info('   ✓ ' . Payment::count()           . ' Payments (1 pending auth)');
        $this->command->info('   ✓ ' . Contract::count()          . ' Contracts (1 expiring soon)');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // ═══════════════════════════════════════════════════════════════════
    //  Procurement Module — Full P2P lifecycle
    //  Tables (in dependency order):
    //     1. procurement_suppliers
    //     2. procurement_budgets
    //     3. procurement_requisitions
    //     4. procurement_requisition_items
    //     5. procurement_tenders
    //     6. procurement_bids
    //     7. procurement_bid_evaluations
    //     8. procurement_purchase_orders
    //     9. procurement_purchase_order_items
    //    10. procurement_goods_receipts
    //    11. procurement_goods_receipt_items
    //    12. procurement_invoices
    //    13. procurement_three_way_matches
    //    14. procurement_payments
    // ═══════════════════════════════════════════════════════════════════

    public function up(): void
    {
        // ── 1. Suppliers ──────────────────────────────────────────────
        Schema::create('procurement_suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200);
            $table->string('code', 50)->unique()->nullable();
            $table->string('email', 150)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('address', 300)->nullable();
            $table->string('tin_number', 50)->nullable()->comment('Tax Identification Number');
            $table->string('bank_name', 100)->nullable();
            $table->string('bank_account', 100)->nullable();
            $table->string('contact_person', 150)->nullable();
            $table->enum('category', ['Goods', 'Services', 'Works', 'Consultancy', 'General'])->default('General');
            $table->enum('status', ['Active', 'Inactive', 'Blacklisted'])->default('Active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // ── 2. Budget Lines ───────────────────────────────────────────
        Schema::create('procurement_budgets', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('title', 200);
            $table->string('department', 150)->nullable();
            $table->string('cost_center', 100)->nullable();
            $table->string('fiscal_year', 10)->default(date('Y'));
            $table->decimal('allocated_amount', 15, 2)->default(0);
            $table->decimal('committed_amount', 15, 2)->default(0); // reserved by approved requisitions
            $table->decimal('expended_amount', 15, 2)->default(0);  // actual POs/invoices
            $table->enum('status', ['Active', 'Exhausted', 'Closed'])->default('Active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('code');
            $table->index('fiscal_year');
        });

        // ── 3. Purchase Requisitions ──────────────────────────────────
        Schema::create('procurement_requisitions', function (Blueprint $table) {
            $table->id();
            $table->string('requisition_number', 50)->unique();
            $table->foreignId('budget_id')->nullable()->constrained('procurement_budgets')->nullOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->string('department', 150)->nullable();
            $table->string('cost_center', 100)->nullable();
            $table->string('budget_code', 50)->nullable();
            $table->enum('category', ['Goods', 'Services', 'Works', 'Consultancy'])->default('Goods');
            $table->enum('procurement_method', [
                'Open Tender', 'Restricted Tender', 'Two-Stage Tender',
                'RFP', 'RFQ', 'Direct Procurement',
            ])->nullable();
            $table->date('required_by_date');
            $table->text('justification')->nullable();
            $table->string('delivery_location', 200)->nullable();
            $table->decimal('estimated_total', 15, 2)->default(0);

            // ── Approval chain: Supervisor → Department Head → Finance → Procurement ──
            $table->enum('overall_status', [
                'Draft', 'Submitted', 'Approved', 'Rejected', 'Converted to PO',
            ])->default('Draft');

            // Stage 1 — Supervisor
            $table->enum('supervisor_status', ['Pending', 'Approved', 'Rejected'])->default('Pending');
            $table->foreignId('supervisor_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('supervisor_approved_at')->nullable();
            $table->text('supervisor_remarks')->nullable();

            // Stage 2 — Department Head
            $table->enum('dept_head_status', ['Pending', 'Approved', 'Rejected'])->default('Pending');
            $table->foreignId('dept_head_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dept_head_approved_at')->nullable();
            $table->text('dept_head_remarks')->nullable();

            // Stage 3 — Finance
            $table->enum('finance_status', ['Pending', 'Approved', 'Rejected'])->default('Pending');
            $table->foreignId('finance_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finance_approved_at')->nullable();
            $table->text('finance_remarks')->nullable();

            // Stage 4 — Procurement (Final)
            $table->enum('procurement_status', ['Pending', 'Approved', 'Rejected'])->default('Pending');
            $table->foreignId('procurement_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('procurement_approved_at')->nullable();
            $table->text('procurement_remarks')->nullable();

            $table->text('attachments')->nullable()->comment('JSON encoded array of file paths');
            $table->timestamps();
            $table->softDeletes();

            $table->index('overall_status');
            $table->index('requested_by');
        });

        // ── 4. Requisition Items ──────────────────────────────────────
        Schema::create('procurement_requisition_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requisition_id')->constrained('procurement_requisitions')->cascadeOnDelete();
            $table->string('description', 300);
            $table->string('unit', 50)->nullable();
            $table->decimal('quantity', 12, 4)->default(1);
            $table->decimal('estimated_unit_price', 15, 2)->default(0);
            $table->decimal('estimated_total', 15, 2)->default(0);
            $table->string('specifications', 500)->nullable();
            $table->timestamps();
        });

        // ── 5. Tenders / RFQs ─────────────────────────────────────────
        Schema::create('procurement_tenders', function (Blueprint $table) {
            $table->id();
            $table->string('tender_number', 50)->unique();
            $table->foreignId('requisition_id')->nullable()->constrained('procurement_requisitions')->nullOnDelete();
            $table->string('title', 300);
            $table->text('description')->nullable();
            $table->enum('method', [
                'Open Tender', 'Restricted Tender', 'Two-Stage Tender',
                'RFP', 'RFQ', 'Direct Procurement',
            ]);
            $table->enum('status', [
                'Draft', 'Published', 'Closed', 'Evaluation', 'Awarded', 'Cancelled',
            ])->default('Draft');
            $table->date('issue_date')->nullable();
            $table->date('submission_deadline');
            $table->date('opening_date')->nullable();
            $table->date('award_date')->nullable();
            $table->decimal('estimated_value', 15, 2)->nullable();
            $table->string('currency', 10)->default('ETB');
            $table->text('evaluation_criteria')->nullable()->comment('JSON: criteria & weights');
            $table->text('terms_and_conditions')->nullable();
            $table->text('attachments')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });

        // ── 6. Bids / Quotations ──────────────────────────────────────
        Schema::create('procurement_bids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tender_id')->constrained('procurement_tenders')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('procurement_suppliers')->cascadeOnDelete();
            $table->string('reference_number', 100)->nullable();
            $table->date('submission_date')->nullable();
            $table->decimal('bid_amount', 15, 2)->nullable();
            $table->string('currency', 10)->default('ETB');
            $table->integer('delivery_days')->nullable();
            $table->enum('status', [
                'Submitted', 'Under Review', 'Shortlisted', 'Awarded', 'Rejected',
            ])->default('Submitted');
            // Evaluation scores
            $table->decimal('technical_score', 5, 2)->nullable();
            $table->decimal('financial_score', 5, 2)->nullable();
            $table->decimal('composite_score', 5, 2)->nullable();
            $table->date('validity_date')->nullable();
            $table->text('notes')->nullable();
            $table->text('attachments')->nullable();
            $table->boolean('conflict_of_interest_declared')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tender_id', 'supplier_id']);
        });

        // ── 7. Bid Evaluations ────────────────────────────────────────
        Schema::create('procurement_bid_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bid_id')->constrained('procurement_bids')->cascadeOnDelete();
            $table->foreignId('evaluator_id')->constrained('users')->cascadeOnDelete();
            $table->enum('stage', ['Preliminary', 'Technical', 'Financial'])->default('Technical');
            $table->decimal('score', 5, 2)->default(0);
            $table->text('comments')->nullable();
            $table->timestamp('evaluated_at')->nullable();
            $table->timestamps();

            $table->unique(['bid_id', 'evaluator_id', 'stage']);
        });

        // ── 8. Purchase Orders ────────────────────────────────────────
        Schema::create('procurement_purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number', 50)->unique();
            $table->integer('version')->default(1);
            $table->foreignId('requisition_id')->nullable()->constrained('procurement_requisitions')->nullOnDelete();
            $table->foreignId('tender_id')->nullable()->constrained('procurement_tenders')->nullOnDelete();
            $table->foreignId('bid_id')->nullable()->constrained('procurement_bids')->nullOnDelete();
            $table->foreignId('supplier_id')->constrained('procurement_suppliers')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();

            $table->date('order_date');
            $table->date('delivery_date');
            $table->date('supplier_acknowledged_at')->nullable();
            $table->string('delivery_location', 200)->nullable();
            $table->string('payment_terms', 100)->nullable();
            $table->string('currency', 10)->default('ETB');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->text('notes')->nullable();

            // ── PO Approval chain: Procurement Officer → Finance → Director ─
            $table->enum('overall_status', [
                'Draft', 'Pending Approval', 'Approved', 'Sent to Supplier',
                'Acknowledged', 'Partially Received', 'Received', 'Closed', 'Cancelled',
            ])->default('Draft');

            $table->enum('procurement_officer_status', ['Pending', 'Approved', 'Rejected'])->default('Pending');
            $table->unsignedBigInteger('procurement_officer_approved_by')->nullable();
            $table->foreign('procurement_officer_approved_by', 'po_officer_approved_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('procurement_officer_approved_at')->nullable();

            $table->enum('finance_status', ['Pending', 'Approved', 'Rejected'])->default('Pending');
            $table->unsignedBigInteger('finance_approved_by')->nullable();
            $table->foreign('finance_approved_by', 'po_finance_approved_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('finance_approved_at')->nullable();

            $table->enum('director_status', ['Pending', 'Approved', 'Rejected'])->default('Pending');
            $table->unsignedBigInteger('director_approved_by')->nullable();
            $table->foreign('director_approved_by', 'po_director_approved_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('director_approved_at')->nullable();

            $table->text('attachments')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('overall_status');
            $table->index('supplier_id');
        });

        // ── 9. PO Line Items ──────────────────────────────────────────
        Schema::create('procurement_purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('procurement_purchase_orders')->cascadeOnDelete();
            $table->string('description', 300);
            $table->string('unit', 50)->nullable();
            $table->decimal('quantity', 12, 4)->default(1);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('total_price', 15, 2)->default(0);
            $table->decimal('received_quantity', 12, 4)->default(0);
            $table->string('specifications', 500)->nullable();
            $table->timestamps();
        });

        // ── 10. Goods Receipts (GRN) ──────────────────────────────────
        Schema::create('procurement_goods_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('grn_number', 50)->unique();
            $table->foreignId('purchase_order_id')->constrained('procurement_purchase_orders')->cascadeOnDelete();
            $table->foreignId('received_by')->constrained('users')->cascadeOnDelete();
            $table->date('receipt_date');
            $table->string('delivery_location', 200)->nullable();
            $table->string('delivery_note_number', 100)->nullable();
            $table->enum('overall_condition', ['Good', 'Partial', 'Rejected'])->default('Good');
            $table->enum('status', ['Draft', 'Inspecting', 'Accepted', 'Rejected', 'Partial'])->default('Draft');
            $table->text('inspection_notes')->nullable();

            // Inspection sign-off
            $table->foreignId('inspected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('inspected_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            $table->text('attachments')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // ── 11. GRN Line Items ────────────────────────────────────────
        Schema::create('procurement_goods_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_receipt_id')->constrained('procurement_goods_receipts')->cascadeOnDelete();
            $table->foreignId('po_item_id')->constrained('procurement_purchase_order_items')->cascadeOnDelete();
            $table->decimal('received_quantity', 12, 4)->default(0);
            $table->decimal('accepted_quantity', 12, 4)->default(0);
            $table->decimal('rejected_quantity', 12, 4)->default(0);
            $table->enum('condition', ['Pass', 'Fail', 'Partial'])->default('Pass');
            $table->text('inspection_remarks')->nullable();
            $table->timestamps();
        });

        // ── 12. Supplier Invoices ─────────────────────────────────────
        Schema::create('procurement_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 100)->unique();
            $table->string('supplier_invoice_number', 100)->nullable();
            $table->foreignId('purchase_order_id')->constrained('procurement_purchase_orders')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('procurement_suppliers')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->date('invoice_date');
            $table->date('due_date');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('currency', 10)->default('ETB');
            $table->enum('status', [
                'Draft', 'Submitted', 'Matched', 'Approved', 'Paid', 'Overdue', 'Disputed', 'Rejected',
            ])->default('Draft');
            $table->text('notes')->nullable();
            $table->text('attachments')->nullable();

            // ── Invoice approval ──
            $table->enum('finance_status', ['Pending', 'Approved', 'Rejected'])->default('Pending');
            $table->foreignId('finance_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finance_approved_at')->nullable();
            $table->text('finance_remarks')->nullable();

            $table->enum('director_status', ['Pending', 'Approved', 'Rejected'])->default('Pending');
            $table->foreignId('director_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('director_approved_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('due_date');
        });

        // ── 13. 3-Way Match Records ───────────────────────────────────
        Schema::create('procurement_three_way_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('procurement_invoices')->cascadeOnDelete();
            $table->foreignId('purchase_order_id')->constrained('procurement_purchase_orders')->cascadeOnDelete();
            $table->foreignId('goods_receipt_id')->nullable()->constrained('procurement_goods_receipts')->nullOnDelete();
            $table->enum('match_status', ['Matched', 'Price Mismatch', 'Quantity Mismatch', 'PO Mismatch', 'Pending', 'Exception'])->default('Pending');
            $table->decimal('po_amount', 15, 2)->default(0);
            $table->decimal('grn_amount', 15, 2)->default(0);
            $table->decimal('invoice_amount', 15, 2)->default(0);
            $table->decimal('variance', 15, 2)->default(0);
            $table->text('exception_notes')->nullable();
            $table->foreignId('matched_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('matched_at')->nullable();
            $table->timestamps();
        });

        // ── 14. Payments ──────────────────────────────────────────────
        Schema::create('procurement_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_reference', 100)->unique();
            $table->foreignId('invoice_id')->constrained('procurement_invoices')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('procurement_suppliers')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('currency', 10)->default('ETB');
            $table->enum('payment_method', ['Bank Transfer', 'Cheque', 'Cash', 'Other'])->default('Bank Transfer');
            $table->string('bank_name', 100)->nullable();
            $table->string('bank_reference', 150)->nullable();
            $table->date('payment_date')->nullable();
            $table->date('scheduled_date')->nullable();
            $table->enum('status', ['Scheduled', 'Pending Approval', 'Approved', 'Processed', 'Failed', 'Cancelled'])->default('Scheduled');
            $table->text('notes')->nullable();

            // ── Payment approval chain ──
            $table->enum('finance_status', ['Pending', 'Approved', 'Rejected'])->default('Pending');
            $table->foreignId('finance_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finance_approved_at')->nullable();

            $table->enum('director_status', ['Pending', 'Approved', 'Rejected'])->default('Pending');
            $table->foreignId('director_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('director_approved_at')->nullable();

            $table->text('attachments')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('payment_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procurement_payments');
        Schema::dropIfExists('procurement_three_way_matches');
        Schema::dropIfExists('procurement_invoices');
        Schema::dropIfExists('procurement_goods_receipt_items');
        Schema::dropIfExists('procurement_goods_receipts');
        Schema::dropIfExists('procurement_purchase_order_items');
        Schema::dropIfExists('procurement_purchase_orders');
        Schema::dropIfExists('procurement_bid_evaluations');
        Schema::dropIfExists('procurement_bids');
        Schema::dropIfExists('procurement_tenders');
        Schema::dropIfExists('procurement_requisition_items');
        Schema::dropIfExists('procurement_requisitions');
        Schema::dropIfExists('procurement_budgets');
        Schema::dropIfExists('procurement_suppliers');
    }
};

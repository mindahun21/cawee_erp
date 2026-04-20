<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_payment_requisitions', function (Blueprint $table) {
            $table->id();

            $table->string('pr_number', 30)->unique()->comment('Auto: PR-2026-0001');
            $table->date('requisition_date');

            // Link to Procurement (optional)
            $table->unsignedBigInteger('procurement_po_id')->nullable()->index()
                ->comment('FK → procurement_purchase_orders (nullable if not PO-linked)');
            $table->unsignedBigInteger('supplier_id')->nullable()->index()
                ->comment('FK → procurement_suppliers');

            // Payee details (denormalized for flexibility)
            $table->string('payee_name');
            $table->string('payee_bank_name')->nullable();
            $table->string('payee_account_number', 50)->nullable();
            $table->string('payee_tin', 30)->nullable()->comment('Tax Identification Number');

            // Invoice details
            $table->string('invoice_number', 60)->nullable();
            $table->date('invoice_date')->nullable();
            $table->string('invoice_attachment')->nullable();

            // Amounts
            $table->decimal('total_amount', 18, 2);
            $table->foreignId('currency_id')->constrained('currencies')->restrictOnDelete();
            $table->decimal('exchange_rate_to_base', 10, 6)->default(1.000000);
            $table->decimal('withholding_tax_amount', 18, 2)->default(0);
            $table->decimal('vat_amount', 18, 2)->default(0);
            $table->decimal('net_payable', 18, 2)->comment('total - wht - vat if payable');

            // 4-dimension coding (mandatory for NGO)
            $table->foreignId('cost_center_id')->constrained('finance_cost_centers')->restrictOnDelete();
            $table->unsignedBigInteger('project_id')->nullable()->index();
            $table->unsignedBigInteger('donor_id')->nullable()->index();
            $table->string('activity_code', 50)->nullable();
            $table->string('donor_code', 50)->nullable();

            // Attachments
            $table->json('document_attachments')->nullable();

            // Workflow
            $table->enum('status', [
                'draft', 'pending_approval', 'approved', 'rejected', 'paid',
            ])->default('draft');
            $table->unsignedTinyInteger('approval_stage')->default(1)->comment('Current approval level');

            // Maker-Checker
            $table->foreignId('prepared_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            // Link to payment voucher when paid
            $table->unsignedBigInteger('payment_voucher_id')->nullable()->index();

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'requisition_date'], 'pr_status_date_idx');
            $table->index(['cost_center_id', 'project_id'], 'pr_dimension_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_payment_requisitions');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_payment_vouchers', function (Blueprint $table) {
            $table->id();

            // ── Identity ───────────────────────────────────────────────────
            $table->string('pv_number', 30)->unique()
                ->comment('Auto-generated: PV-2026-0001');

            $table->foreignId('accounting_period_id')
                ->constrained('finance_accounting_periods')->restrictOnDelete();

            $table->date('payment_date');

            // ── Payee ──────────────────────────────────────────────────────
            $table->string('payee_name', 200);
            $table->enum('payee_type', ['supplier', 'employee', 'other'])->default('supplier');
            $table->unsignedBigInteger('payee_id')->nullable()
                ->comment('Polymorphic hint: supplier_id or employee_id');
            $table->string('payee_tin', 30)->nullable()
                ->comment('Tax Identification Number for WHT documentation');

            // ── Payment Method ─────────────────────────────────────────────
            $table->foreignId('bank_account_id')->nullable()->constrained('finance_bank_accounts')->nullOnDelete();
            $table->enum('payment_method', ['cash', 'cheque', 'bank_transfer', 'mobile_money'])
                ->default('bank_transfer');
            $table->string('cheque_number', 30)->nullable();
            $table->string('transfer_reference', 60)->nullable();

            // ── Amount \u0026 Tax ────────────────────────────────────────────────
            $table->decimal('gross_amount', 15, 2)
                ->comment('Invoice/contract amount before tax');
            $table->foreignId('currency_id')->constrained('currencies')->restrictOnDelete();
            $table->decimal('exchange_rate_to_base', 15, 6)->default(1.000000);

            $table->decimal('withholding_tax_rate', 6, 4)->default(0)
                ->comment('e.g. 0.0200 = 2%');
            $table->decimal('withholding_tax_amount', 15, 2)->default(0);

            $table->enum('vat_type', ['collected', 'payable', 'exempt', 'none'])->default('none');
            $table->decimal('vat_rate', 6, 4)->default(0);
            $table->decimal('vat_amount', 15, 2)->default(0);

            $table->decimal('net_amount', 15, 2)
                ->comment('Amount actually paid out: gross - wht');

            // ── NGO Dimensions ─────────────────────────────────────────────
            $table->foreignId('project_id')->nullable()->constrained('hr_projects')->nullOnDelete();
            $table->foreignId('cost_center_id')->nullable()->constrained('finance_cost_centers')->nullOnDelete();
            $table->foreignId('donor_id')->nullable()->constrained('donors')->nullOnDelete();
            $table->string('activity_code', 50)->nullable();
            $table->string('donor_code', 50)->nullable();

            // ── Source Link (from Procurement) ─────────────────────────────
            $table->unsignedBigInteger('payment_requisition_id')->nullable()
                ->comment('FK to finance_payment_requisitions (Phase 4)');
            $table->string('invoice_number', 60)->nullable();
            $table->date('invoice_date')->nullable();

            // ── GL Link ────────────────────────────────────────────────────
            $table->foreignId('journal_entry_id')->nullable()->constrained('finance_journal_entries')->nullOnDelete();

            // ── Workflow ───────────────────────────────────────────────────
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'posted', 'rejected'])
                ->default('draft');
            $table->foreignId('prepared_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('posted_at')->nullable();

            // ── Attachments ────────────────────────────────────────────────
            $table->json('document_attachments')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_payment_vouchers');
    }
};

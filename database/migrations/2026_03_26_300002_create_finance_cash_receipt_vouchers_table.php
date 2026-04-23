<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_cash_receipt_vouchers', function (Blueprint $table) {
            $table->id();

            // ── Identity ───────────────────────────────────────────────────
            $table->string('crv_number', 30)->unique()
                ->comment('Auto-generated: CRV-2026-0001');

            $table->foreignId('accounting_period_id')
                ->constrained('finance_accounting_periods')->restrictOnDelete();

            $table->date('receipt_date');
            $table->string('received_from', 200);

            // ── Source ─────────────────────────────────────────────────────
            $table->foreignId('donor_id')->nullable()->constrained('donors')->nullOnDelete();
            $table->foreignId('bank_account_id')->nullable()->constrained('finance_bank_accounts')->nullOnDelete();
            $table->enum('income_type', ['grant', 'donation', 'service', 'interest', 'other'])
                ->default('donation');

            // ── Amount ─────────────────────────────────────────────────────
            $table->decimal('amount', 15, 2);
            $table->foreignId('currency_id')->constrained('currencies')->restrictOnDelete();
            $table->decimal('exchange_rate_to_base', 15, 6)->default(1.000000);
            $table->decimal('amount_in_base', 15, 2)->default(0)
                ->comment('Computed: amount × exchange_rate_to_base');

            // ── NGO Dimensions ─────────────────────────────────────────────
            $table->foreignId('project_id')->nullable()->constrained('hr_projects')->nullOnDelete();
            $table->foreignId('cost_center_id')->nullable()->constrained('finance_cost_centers')->nullOnDelete();
            $table->string('activity_code', 50)->nullable();
            $table->string('donor_code', 50)->nullable();

            // ── GL Link ────────────────────────────────────────────────────
            $table->foreignId('journal_entry_id')->nullable()->constrained('finance_journal_entries')->nullOnDelete();

            // ── Workflow ───────────────────────────────────────────────────
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'posted', 'rejected'])
                ->default('draft');
            $table->foreignId('prepared_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('posted_at')->nullable();

            // ── Attachments & Notes ────────────────────────────────────────
            $table->json('document_attachments')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_cash_receipt_vouchers');
    }
};

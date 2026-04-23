<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_fund_transfers', function (Blueprint $table) {
            $table->id();

            $table->string('transfer_number', 30)->unique();

            $table->foreignId('accounting_period_id')
                ->constrained('finance_accounting_periods')->restrictOnDelete();

            $table->date('transfer_date');

            // ── From / To ──────────────────────────────────────────────────
            $table->foreignId('from_bank_account_id')
                ->constrained('finance_bank_accounts')->restrictOnDelete();

            $table->foreignId('to_bank_account_id')
                ->constrained('finance_bank_accounts')->restrictOnDelete();

            $table->foreignId('from_cost_center_id')
                ->nullable()->constrained('finance_cost_centers')->nullOnDelete();

            $table->foreignId('to_cost_center_id')
                ->nullable()->constrained('finance_cost_centers')->nullOnDelete();

            // ── Amount ─────────────────────────────────────────────────────
            $table->decimal('amount', 15, 2);
            $table->foreignId('currency_id')->constrained('currencies')->restrictOnDelete();
            $table->decimal('exchange_rate_to_base', 15, 6)->default(1.000000);

            // ── NGO Dimensions ─────────────────────────────────────────────
            $table->foreignId('project_id')->nullable()->constrained('hr_projects')->nullOnDelete();
            $table->foreignId('donor_id')->nullable()->constrained('donors')->nullOnDelete();
            $table->text('purpose');

            // ── GL Link ────────────────────────────────────────────────────
            $table->foreignId('journal_entry_id')
                ->nullable()->constrained('finance_journal_entries')->nullOnDelete();

            // ── Workflow: HO → Field confirmation flow ─────────────────────
            $table->enum('status', [
                'draft',
                'approved',
                'remitted',
                'confirmed',
                'reconciled',
            ])->default('draft');

            $table->foreignId('prepared_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->string('confirmation_reference', 80)->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_fund_transfers');
    }
};

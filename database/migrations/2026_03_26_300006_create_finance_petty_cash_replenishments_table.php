<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_petty_cash_replenishments', function (Blueprint $table) {
            $table->id();

            $table->string('replenishment_number', 30)->unique();

            $table->foreignId('petty_cash_fund_id')
                ->constrained('finance_petty_cash_funds')->restrictOnDelete();

            $table->foreignId('accounting_period_id')
                ->constrained('finance_accounting_periods')->restrictOnDelete();

            $table->date('request_date');
            $table->decimal('amount_requested', 15, 2);
            $table->decimal('amount_approved', 15, 2)->nullable();
            $table->decimal('balance_before', 15, 2)
                ->comment('Petty cash balance at the time of request');

            $table->text('justification')->nullable();

            // ── GL Link ────────────────────────────────────────────────────
            $table->foreignId('journal_entry_id')
                ->nullable()->constrained('finance_journal_entries')->nullOnDelete();

            $table->foreignId('bank_account_id')
                ->nullable()->constrained('finance_bank_accounts')->nullOnDelete()
                ->comment('Bank account from which replenishment is drawn');

            // ── Workflow ───────────────────────────────────────────────────
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected', 'disbursed'])
                ->default('draft');
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('disbursed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('disbursed_at')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_petty_cash_replenishments');
    }
};

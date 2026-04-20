<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_petty_cash_payments', function (Blueprint $table) {
            $table->id();

            $table->string('payment_number', 30)->unique();

            $table->foreignId('petty_cash_fund_id')
                ->constrained('finance_petty_cash_funds')->restrictOnDelete();

            $table->foreignId('accounting_period_id')
                ->constrained('finance_accounting_periods')->restrictOnDelete();

            $table->date('payment_date');
            $table->string('payee_name', 200);
            $table->text('description');

            $table->decimal('amount', 15, 2);
            $table->string('receipt_number', 60)->nullable();
            $table->string('document_attachment', 255)->nullable();

            // ── NGO Dimensions ─────────────────────────────────────────────
            $table->foreignId('chart_of_account_id')
                ->nullable()
                ->constrained('finance_chart_of_accounts')->nullOnDelete()
                ->comment('Expense account to debit');
            $table->string('activity_code', 50)->nullable();
            $table->foreignId('project_id')->nullable()->constrained('hr_projects')->nullOnDelete();
            $table->foreignId('donor_id')->nullable()->constrained('donors')->nullOnDelete();

            // ── Workflow ───────────────────────────────────────────────────
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('prepared_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_petty_cash_payments');
    }
};

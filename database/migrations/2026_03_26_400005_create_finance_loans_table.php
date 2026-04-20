<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_loans', function (Blueprint $table) {
            $table->id();

            $table->string('loan_reference', 30)->unique()->comment('Auto: LN-2026-0001');

            $table->enum('borrower_type', ['employee', 'organization']);
            $table->unsignedBigInteger('borrower_id')->comment('FK → employees.id or organizations.id');

            $table->string('loan_purpose')->nullable();

            $table->decimal('principal_amount', 18, 2);
            $table->decimal('interest_rate', 6, 4)->default(0)->comment('Annual rate e.g. 0.1000 = 10%');
            $table->unsignedTinyInteger('tenor_months')->comment('Loan term in months');

            $table->date('disbursement_date');
            $table->date('start_repayment_date');
            $table->date('maturity_date')->nullable()->comment('Computed: start + tenor_months');

            $table->decimal('outstanding_balance', 18, 2)->comment('Decrements as repayments are made');
            $table->decimal('total_interest', 18, 2)->default(0);

            $table->foreignId('bank_account_id')->constrained('finance_bank_accounts')->restrictOnDelete();
            $table->foreignId('currency_id')->constrained('currencies')->restrictOnDelete();

            // GL links
            $table->foreignId('journal_entry_id')->nullable()->constrained('finance_journal_entries')->nullOnDelete()
                ->comment('Disbursement JE');

            $table->enum('status', ['active', 'fully_paid', 'written_off'])->default('active');

            $table->foreignId('prepared_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['borrower_type', 'borrower_id'], 'loan_borrower_idx');
            $table->index('status', 'loan_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_loans');
    }
};

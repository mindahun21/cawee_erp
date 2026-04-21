<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_loan_repayment_schedules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('loan_id')
                ->constrained('finance_loans')
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('installment_number')->comment('1, 2, 3, …');
            $table->date('due_date');

            $table->decimal('principal_amount', 18, 2);
            $table->decimal('interest_amount', 18, 2)->default(0);
            $table->decimal('total_due', 18, 2)->comment('principal + interest');

            $table->decimal('paid_amount', 18, 2)->default(0);
            $table->date('paid_date')->nullable();

            // Link to PV or manual JE
            $table->foreignId('journal_entry_id')->nullable()->constrained('finance_journal_entries')->nullOnDelete();

            $table->enum('status', ['pending', 'paid', 'overdue', 'partially_paid'])->default('pending');

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['loan_id', 'installment_number'], 'loan_schedule_unique');
            $table->index(['status', 'due_date'], 'ls_status_due_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_loan_repayment_schedules');
    }
};

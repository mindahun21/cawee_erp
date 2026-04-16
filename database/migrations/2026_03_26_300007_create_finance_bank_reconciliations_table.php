<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_bank_reconciliations', function (Blueprint $table) {
            $table->id();

            $table->string('reference', 30)->unique();

            $table->foreignId('bank_account_id')
                ->constrained('finance_bank_accounts')->restrictOnDelete();

            $table->foreignId('accounting_period_id')
                ->constrained('finance_accounting_periods')->restrictOnDelete();

            $table->date('statement_date')
                ->comment('The closing date of the bank statement');

            $table->decimal('statement_balance', 15, 2)
                ->comment('Closing balance per bank statement');

            $table->decimal('gl_balance', 15, 2)
                ->comment('GL balance for this account on statement_date');

            $table->decimal('outstanding_deposits', 15, 2)->default(0)
                ->comment('Deposits in transit not yet on statement');

            $table->decimal('outstanding_cheques', 15, 2)->default(0)
                ->comment('Issued cheques not yet cleared by bank');

            $table->decimal('adjusted_bank_balance', 15, 2)->default(0)
                ->comment('statement_balance + outstanding_deposits - outstanding_cheques');

            $table->decimal('difference', 15, 2)->default(0)
                ->comment('Adjusted bank balance - GL balance (should be 0 when reconciled)');

            $table->enum('status', ['in_progress', 'reconciled', 'locked'])->default('in_progress');

            $table->foreignId('prepared_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reconciled_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('finance_bank_reconciliation_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('reconciliation_id')
                ->constrained('finance_bank_reconciliations')->cascadeOnDelete();

            $table->foreignId('journal_entry_line_id')
                ->nullable()->constrained('finance_journal_entry_lines')->nullOnDelete();

            $table->enum('item_type', ['deposit', 'payment', 'bank_charge', 'interest', 'other']);
            $table->date('transaction_date');
            $table->string('description', 255);
            $table->decimal('amount', 15, 2);
            $table->boolean('is_cleared')->default(false)
                ->comment('True when the item appears on the bank statement');
            $table->date('cleared_date')->nullable();
            $table->string('bank_reference', 80)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_bank_reconciliation_items');
        Schema::dropIfExists('finance_bank_reconciliations');
    }
};

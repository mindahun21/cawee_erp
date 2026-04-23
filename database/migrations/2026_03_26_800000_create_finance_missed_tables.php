<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (!Schema::hasTable('finance_bank_advices')) Schema::create('finance_bank_advices', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number', 30)->unique();
            $table->date('advice_date');
            $table->foreignId('bank_account_id')->constrained('finance_bank_accounts')->cascadeOnDelete();
            $table->enum('advice_type', ['credit', 'debit']);
            $table->decimal('amount', 18, 2)->default(0);
            $table->foreignId('currency_id')->constrained('currencies');
            $table->string('description', 255);
            $table->enum('status', ['draft', 'posted'])->default('draft');
            $table->foreignId('journal_entry_id')->nullable()->constrained('finance_journal_entries')->nullOnDelete();
            $table->timestamps();
        });

        if (!Schema::hasTable('finance_bank_deposit_slips')) Schema::create('finance_bank_deposit_slips', function (Blueprint $table) {
            $table->id();
            $table->string('slip_number', 30)->unique();
            $table->date('deposit_date');
            $table->foreignId('bank_account_id')->constrained('finance_bank_accounts')->cascadeOnDelete();
            $table->decimal('total_amount', 18, 2)->default(0);
            $table->foreignId('currency_id')->constrained('currencies');
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'deposited'])->default('draft');
            $table->foreignId('prepared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        if (!Schema::hasTable('finance_reference_pad_books')) Schema::create('finance_reference_pad_books', function (Blueprint $table) {
            $table->id();
            $table->string('pad_number', 50)->unique();
            $table->enum('book_type', ['crv', 'pv', 'cheque', 'receipt']);
            $table->string('prefix', 10)->nullable();
            $table->unsignedInteger('start_sequence');
            $table->unsignedInteger('end_sequence');
            $table->unsignedInteger('current_sequence');
            $table->boolean('is_active')->default(true);
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('finance_reference_pad_books');
        Schema::dropIfExists('finance_bank_deposit_slips');
        Schema::dropIfExists('finance_bank_advices');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_income_registers', function (Blueprint $table) {
            $table->id();

            $table->string('reference', 30)->unique()->comment('Auto: IR-2026-0001');
            $table->date('income_date');
            $table->string('source_name')->comment('Name of income source');

            $table->unsignedBigInteger('donor_id')->nullable()->index();
            $table->enum('income_type', ['grant', 'service_fee', 'interest', 'other'])->default('grant');

            $table->decimal('amount', 18, 2);
            $table->foreignId('currency_id')->constrained('currencies')->restrictOnDelete();
            $table->decimal('exchange_rate_to_base', 10, 6)->default(1.000000);
            $table->decimal('amount_in_base', 18, 2)->comment('amount × exchange_rate');

            $table->unsignedBigInteger('project_id')->nullable()->index();
            $table->foreignId('cost_center_id')->constrained('finance_cost_centers')->restrictOnDelete();

            // Optional direct bank deposit
            $table->foreignId('bank_account_id')->nullable()->constrained('finance_bank_accounts')->nullOnDelete();

            $table->string('receipt_reference', 80)->nullable();
            $table->text('description')->nullable();
            $table->json('document_attachments')->nullable();

            $table->enum('status', ['draft', 'confirmed', 'posted'])->default('draft');

            // GL link
            $table->foreignId('journal_entry_id')->nullable()->constrained('finance_journal_entries')->nullOnDelete();

            // Maker-Checker
            $table->foreignId('prepared_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'income_date'], 'ir_status_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_income_registers');
    }
};

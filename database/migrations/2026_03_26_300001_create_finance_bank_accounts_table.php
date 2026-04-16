<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_bank_accounts', function (Blueprint $table) {
            $table->id();

            $table->string('account_name', 120);
            $table->string('bank_name', 120);
            $table->string('account_number', 60)->unique();
            $table->string('branch', 100)->nullable();
            $table->string('swift_code', 20)->nullable();
            $table->enum('account_type', ['current', 'savings', 'project_specific'])
                ->default('current');

            // ── Financial dimensions ───────────────────────────────────────
            $table->foreignId('currency_id')
                ->constrained('currencies')->restrictOnDelete();

            $table->foreignId('chart_of_account_id')
                ->nullable()
                ->constrained('finance_chart_of_accounts')->nullOnDelete()
                ->comment('The GL account this bank account maps to (control account = bank)');

            $table->foreignId('cost_center_id')
                ->nullable()
                ->constrained('finance_cost_centers')->nullOnDelete();

            $table->foreignId('donor_id')
                ->nullable()
                ->constrained('donors')->nullOnDelete()
                ->comment('Set when this is a donor-restricted account');

            // ── Balance tracking ───────────────────────────────────────────
            $table->date('balance_as_of_date')->nullable();
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->decimal('current_balance', 15, 2)->default(0);

            // ── Status ─────────────────────────────────────────────────────
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_bank_accounts');
    }
};

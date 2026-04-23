<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_petty_cash_funds', function (Blueprint $table) {
            $table->id();

            $table->string('fund_name', 120);
            $table->string('fund_code', 20)->unique();

            $table->foreignId('cashier_id')
                ->constrained('finance_cashiers')->restrictOnDelete();

            $table->foreignId('cost_center_id')
                ->constrained('finance_cost_centers')->restrictOnDelete();

            $table->foreignId('currency_id')
                ->constrained('currencies')->restrictOnDelete();

            $table->foreignId('chart_of_account_id')
                ->nullable()
                ->constrained('finance_chart_of_accounts')->nullOnDelete()
                ->comment('Petty cash GL account for this fund');

            // ── Balance ────────────────────────────────────────────────────
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->decimal('max_limit', 15, 2)->default(5000)
                ->comment('Maximum fund balance before mandatory replenishment');

            // ── Status ─────────────────────────────────────────────────────
            $table->enum('status', ['active', 'suspended', 'closed'])->default('active');
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_petty_cash_funds');
    }
};

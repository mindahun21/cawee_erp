<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_budget_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained('finance_budgets')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('finance_chart_of_accounts');
            $table->string('activity_code', 50)->nullable();
            $table->string('activity_description', 300)->nullable();

            // Quarterly breakdown
            $table->decimal('q1_amount', 18, 2)->default(0);
            $table->decimal('q2_amount', 18, 2)->default(0);
            $table->decimal('q3_amount', 18, 2)->default(0);
            $table->decimal('q4_amount', 18, 2)->default(0);
            $table->decimal('total_budgeted', 18, 2)->default(0); // computed: sum(q1..q4)

            // Actuals (auto-updated)
            $table->decimal('committed', 18, 2)->default(0);
            $table->decimal('encumbered', 18, 2)->default(0);
            $table->decimal('actual', 18, 2)->default(0);

            $table->timestamps();
            $table->index('budget_id');
            $table->index('account_id');
        });
    }

    public function down(): void { Schema::dropIfExists('finance_budget_lines'); }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_cost_buildups', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 30)->unique();
            $table->foreignId('budget_id')->nullable()->constrained('finance_budgets')->nullOnDelete();
            $table->foreignId('budget_line_id')->nullable()->constrained('finance_budget_lines')->nullOnDelete();
            $table->foreignId('account_id')->nullable()->constrained('finance_chart_of_accounts')->nullOnDelete();
            $table->date('transaction_date');
            $table->string('description', 500);
            $table->decimal('amount', 18, 2)->default(0);
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->decimal('exchange_rate_to_base', 10, 6)->default(1);

            // 4-dimension coding
            $table->string('activity_code', 50)->nullable();
            $table->foreignId('project_id')->nullable()->constrained('hr_projects')->nullOnDelete();
            $table->foreignId('cost_center_id')->nullable()->constrained('finance_cost_centers')->nullOnDelete();
            $table->foreignId('donor_id')->nullable()->constrained('donors')->nullOnDelete();

            $table->foreignId('prepared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index('budget_id');
        });

        Schema::create('finance_declared_taxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_type_id')->constrained('finance_tax_types');
            $table->string('declaration_period', 20)->comment('e.g. 2026-Q1 or 2026-01');
            $table->date('declaration_date');
            $table->decimal('total_income', 18, 2)->default(0);
            $table->decimal('taxable_income', 18, 2)->default(0);
            $table->decimal('tax_payable', 18, 2)->default(0);
            $table->decimal('paid_amount', 18, 2)->default(0);
            $table->date('payment_date')->nullable();
            $table->string('reference_number', 50)->nullable();
            $table->enum('status', ['draft', 'filed', 'paid'])->default('draft');
            $table->string('document_attachment')->nullable();
            $table->timestamps();
            $table->index(['tax_type_id', 'declaration_period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_declared_taxes');
        Schema::dropIfExists('finance_cost_buildups');
    }
};

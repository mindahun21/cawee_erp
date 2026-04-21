<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_budgets', function (Blueprint $table) {
            $table->id();
            $table->string('budget_code', 30)->unique();
            $table->string('name', 200);
            $table->foreignId('budget_type_id')->constrained('finance_budget_types');
            $table->foreignId('donor_id')->nullable()->constrained('donors')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('hr_projects')->nullOnDelete();
            $table->foreignId('cost_center_id')->nullable()->constrained('finance_cost_centers')->nullOnDelete();
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->unsignedSmallInteger('fiscal_year');

            // Amounts (auto-updated by service layer)
            $table->decimal('total_budget_amount', 18, 2)->default(0);
            $table->decimal('committed_amount', 18, 2)->default(0);
            $table->decimal('encumbered_amount', 18, 2)->default(0);
            $table->decimal('actual_spent', 18, 2)->default(0);

            $table->enum('status', ['draft', 'approved', 'active', 'closed', 'cancelled'])->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['fiscal_year', 'status']);
            $table->index('donor_id');
            $table->index('project_id');
        });
    }

    public function down(): void { Schema::dropIfExists('finance_budgets'); }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_budget_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained('finance_budgets')->cascadeOnDelete();
            $table->unsignedTinyInteger('revision_number')->default(1);
            $table->date('revision_date');
            $table->text('reason');
            $table->decimal('old_total', 18, 2)->default(0);
            $table->decimal('new_total', 18, 2)->default(0);
            $table->foreignId('revised_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index('budget_id');
        });

        Schema::create('finance_commitments', function (Blueprint $table) {
            $table->id();
            $table->enum('source_type', ['procurement_po', 'contract', 'manual']);
            $table->unsignedBigInteger('source_id')->nullable();
            $table->foreignId('budget_id')->constrained('finance_budgets')->cascadeOnDelete();
            $table->foreignId('budget_line_id')->constrained('finance_budget_lines')->cascadeOnDelete();
            $table->decimal('amount', 18, 2)->default(0);
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->date('commitment_date');
            $table->enum('status', ['open', 'partially_utilized', 'fully_utilized', 'cancelled'])->default('open');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['budget_id', 'status']);
        });

        Schema::create('finance_encumbrances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commitment_id')->nullable()->constrained('finance_commitments')->nullOnDelete();
            $table->string('source_type', 100)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->foreignId('budget_id')->constrained('finance_budgets')->cascadeOnDelete();
            $table->foreignId('budget_line_id')->constrained('finance_budget_lines')->cascadeOnDelete();
            $table->decimal('amount', 18, 2)->default(0);
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->date('encumbrance_date');
            $table->enum('status', ['open', 'partially_liquidated', 'fully_liquidated', 'cancelled'])->default('open');
            $table->timestamps();
            $table->index(['budget_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_encumbrances');
        Schema::dropIfExists('finance_commitments');
        Schema::dropIfExists('finance_budget_revisions');
    }
};

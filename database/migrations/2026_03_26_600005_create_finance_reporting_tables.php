<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('finance_financial_statements')) {
        Schema::create('finance_financial_statements', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 30)->unique();
            $table->enum('statement_type', [
                'trial_balance', 'income_statement', 'balance_sheet',
                'cash_flow', 'budget_vs_actual', 'donor_expenditure',
                'tax_summary', 'payroll_summary',
            ]);
            $table->string('title', 200);
            $table->foreignId('accounting_period_id')->nullable()->constrained('finance_accounting_periods')->nullOnDelete();
            $table->unsignedSmallInteger('fiscal_year');
            $table->date('as_of_date');
            $table->foreignId('donor_id')->nullable()->constrained('donors')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('hr_projects')->nullOnDelete();
            $table->foreignId('cost_center_id')->nullable()->constrained('finance_cost_centers')->nullOnDelete();
            $table->json('parameters')->nullable()->comment('Filters used to generate');
            $table->enum('status', ['draft', 'finalized', 'submitted'])->default('draft');
            $table->foreignId('prepared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('file_path')->nullable()->comment('Generated PDF/XLSX path');
            $table->timestamps();
            $table->index(['statement_type', 'fiscal_year']);
        });
        } // end if financial_statements

        if (!Schema::hasTable('finance_project_progress_payments')) Schema::create('finance_project_progress_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('hr_projects');
            $table->foreignId('donor_id')->constrained('donors');
            $table->date('payment_date');
            $table->decimal('amount', 18, 2)->default(0);
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->string('description', 500)->nullable();
            $table->string('invoice_reference', 100)->nullable();
            $table->foreignId('bank_account_id')->nullable()->constrained('finance_bank_accounts')->nullOnDelete();
            $table->decimal('cumulative_received', 18, 2)->default(0);
            $table->enum('status', ['received', 'partially_spent', 'fully_utilized'])->default('received');
            $table->foreignId('journal_entry_id')->nullable()->constrained('finance_journal_entries')->nullOnDelete();
            $table->timestamps();
            $table->index(['project_id', 'donor_id']);
        });

        if (!Schema::hasTable('finance_inventory_taking_sheets')) Schema::create('finance_inventory_taking_sheets', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 30)->unique();
            $table->date('taking_date');
            $table->foreignId('cost_center_id')->nullable()->constrained('finance_cost_centers')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('hr_projects')->nullOnDelete();
            $table->foreignId('conducted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['draft', 'verified', 'submitted'])->default('draft');
            $table->timestamps();
        });

        if (!Schema::hasTable('finance_inventory_taking_sheet_items')) Schema::create('finance_inventory_taking_sheet_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inventory_taking_sheet_id');
            $table->foreign('inventory_taking_sheet_id', 'fk_inv_sheet_items_sheet_id')
                ->references('id')->on('finance_inventory_taking_sheets')->cascadeOnDelete();
            $table->enum('item_type', ['asset', 'inventory_item']);
            $table->unsignedBigInteger('item_id')->nullable();
            $table->string('item_description', 300);
            $table->decimal('book_quantity', 12, 4)->default(0);
            $table->decimal('physical_quantity', 12, 4)->default(0);
            $table->decimal('variance', 12, 4)->default(0); // physical - book
            $table->decimal('unit_cost', 18, 2)->default(0);
            $table->decimal('variance_amount', 18, 2)->default(0); // variance * unit_cost
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index('inventory_taking_sheet_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_inventory_taking_sheet_items');
        Schema::dropIfExists('finance_inventory_taking_sheets');
        Schema::dropIfExists('finance_project_progress_payments');
        Schema::dropIfExists('finance_financial_statements');
    }
};

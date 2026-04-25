<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('finance_payroll_summaries')) {
            $requiredColumns = [
                'id',
                'payroll_month',
                'payroll_year',
                'employee_id',
                'status',
            ];

            $missingColumns = [];
            foreach ($requiredColumns as $column) {
                if (! Schema::hasColumn('finance_payroll_summaries', $column)) {
                    $missingColumns[] = $column;
                }
            }

            throw_if(
                $missingColumns !== [],
                "Error: 'finance_payroll_summaries' already exists but is missing expected columns: " . implode(', ', $missingColumns)
            );

            return;
        }

        Schema::create('finance_payroll_summaries', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('payroll_month');  // 1-12
            $table->unsignedSmallInteger('payroll_year');
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('payroll_id')->nullable()->constrained('payroll')->nullOnDelete();
            $table->unsignedBigInteger('department_id')->nullable(); // no FK — departments table may not exist yet
            $table->foreignId('cost_center_id')->nullable()->constrained('finance_cost_centers')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('hr_projects')->nullOnDelete();
            $table->foreignId('donor_id')->nullable()->constrained('donors')->nullOnDelete();

            // GL amounts
            $table->decimal('basic_salary', 18, 2)->default(0);
            $table->decimal('allowances_total', 18, 2)->default(0);
            $table->decimal('gross_pay', 18, 2)->default(0);
            $table->decimal('income_tax_withheld', 18, 2)->default(0);
            $table->decimal('pension_employee', 18, 2)->default(0);
            $table->decimal('pension_employer', 18, 2)->default(0);
            $table->decimal('other_deductions', 18, 2)->default(0);
            $table->decimal('deductions_total', 18, 2)->default(0);
            $table->decimal('net_pay', 18, 2)->default(0);
            $table->decimal('employer_total_cost', 18, 2)->default(0); // net_pay + pension_employer

            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();

            $table->enum('status', ['draft', 'journal_posted'])->default('draft');

            $table->foreignId('journal_entry_id')->nullable()->constrained('finance_journal_entries')->nullOnDelete();
            $table->foreignId('prepared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['employee_id', 'payroll_month', 'payroll_year'], 'payroll_summary_unique');
            $table->index(['payroll_year', 'payroll_month']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_payroll_summaries');
    }
};

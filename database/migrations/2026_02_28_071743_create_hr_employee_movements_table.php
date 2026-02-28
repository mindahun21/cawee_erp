<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * hr_employee_movements
 * Tracks promotions, demotions, lateral transfers, and department changes over time.
 * Each row is a snapshot: from_X → to_X with an effective date and approved_by.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_employee_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();

            $table->enum('movement_type', [
                'Promotion',
                'Demotion',
                'Transfer',        // lateral department/project move
                'Grade Change',    // salary grade adjustment without title change
                'Title Change',    // title rename, no grade change
            ]);

            // --- Department columns ---
            $table->foreignId('from_department_id')->nullable()->constrained('hr_departments')->nullOnDelete();
            $table->foreignId('to_department_id')->nullable()->constrained('hr_departments')->nullOnDelete();

            // --- Job Position columns ---
            $table->foreignId('from_job_position_id')->nullable()->constrained('hr_job_positions')->nullOnDelete();
            $table->foreignId('to_job_position_id')->nullable()->constrained('hr_job_positions')->nullOnDelete();

            // --- Salary columns ---
            $table->decimal('from_salary', 12, 2)->nullable();
            $table->decimal('to_salary', 12, 2)->nullable();

            // --- Salary Grade columns ---
            $table->foreignId('from_salary_grade_id')->nullable()->constrained('salary_grades')->nullOnDelete();
            $table->foreignId('to_salary_grade_id')->nullable()->constrained('salary_grades')->nullOnDelete();

            $table->date('effective_date');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reference_number', 50)->nullable();
            $table->text('reason')->nullable();
            $table->string('status', 30)->default('Approved'); // Draft, Pending Approval, Approved, Rejected
            $table->string('attachment_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_employee_movements');
    }
};

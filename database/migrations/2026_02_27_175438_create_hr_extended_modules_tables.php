<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * HR Extended Modules
 *
 * - Employee Contracts (linked to ContractType)
 * - Employee Dependents
 * - Employee Trainings (linked to TrainingType)
 * - Layoff Checklist items (used by offboarding module as a print/sign list)
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Employee Contracts ───────────────────────────────────────
        Schema::create('hr_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('contract_type_id')->nullable()->constrained('hr_contract_types')->nullOnDelete();
            $table->string('contract_number', 50)->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->decimal('salary', 12, 2)->nullable();
            $table->string('status', 20)->default('Active'); // Active, Expired, Terminated
            $table->text('notes')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamps();
        });

        // ── Employee Dependents ──────────────────────────────────────
        Schema::create('hr_dependents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('full_name', 150);
            $table->string('relationship', 50); // Spouse, Child, Parent, etc.
            $table->date('date_of_birth')->nullable();
            $table->string('national_id', 50)->nullable();
            $table->string('phone_number', 20)->nullable();
            $table->boolean('is_beneficiary')->default(false);
            $table->timestamps();
        });

        // ── Employee Trainings ───────────────────────────────────────
        Schema::create('hr_trainings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('training_type_id')->nullable()->constrained('hr_training_types')->nullOnDelete();
            $table->string('title', 200);
            $table->string('institution', 200)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->integer('duration_days')->nullable();
            $table->decimal('cost', 12, 2)->nullable();
            $table->string('certificate_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // ── Layoff Checklist Template Items ─────────────────────────
        // (Actual employee-level offboarding checklists are in EmployeeOnboarding)
        Schema::create('hr_layoff_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->string('responsible_party', 100)->nullable(); // HR, Finance, IT, etc.
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_layoff_checklist_items');
        Schema::dropIfExists('hr_trainings');
        Schema::dropIfExists('hr_dependents');
        Schema::dropIfExists('hr_contracts');
    }
};

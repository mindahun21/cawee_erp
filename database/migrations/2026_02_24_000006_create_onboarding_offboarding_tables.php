<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Onboarding checklist templates (configurable)
        Schema::create('onboarding_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->enum('phase', ['Onboarding', 'Offboarding'])->default('Onboarding');
            $table->enum('category', ['Document to Sign', 'Form to Fill', 'Training', 'Equipment', 'Other'])->default('Document to Sign');
            $table->string('document_template', 255)->nullable(); // path to PDF template
            $table->boolean('requires_signature')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Per-employee onboarding/offboarding progress
        Schema::create('employee_onboarding', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('checklist_item_id')->constrained('onboarding_checklist_items');
            $table->enum('phase', ['Onboarding', 'Offboarding'])->default('Onboarding');
            $table->boolean('completed')->default(false);
            $table->date('completed_at')->nullable();
            $table->string('signed_document', 255)->nullable(); // uploaded signed PDF
            $table->foreignId('verified_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamps();

            $table->unique(['employee_id', 'checklist_item_id', 'phase']);
        });

        // Exit interview
        Schema::create('exit_interviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('termination_date');
            $table->string('starting_position', 150)->nullable();
            $table->string('ending_position', 150)->nullable();
            $table->text('liked_most')->nullable();
            $table->text('liked_least')->nullable();
            $table->text('reason_for_leaving')->nullable();
            $table->json('ratings')->nullable(); // {satisfaction scores keyed by category}
            $table->text('additional_comments')->nullable();
            $table->foreignId('interviewer_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->date('interview_date')->nullable();
            $table->timestamps();
        });

        // Clearance form (offboarding)
        Schema::create('clearance_forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('employment_start_date')->nullable();
            $table->date('employment_end_date')->nullable();
            $table->string('employment_type', 100)->nullable();
            $table->text('organizational_property')->nullable();
            $table->string('reason_of_exit', 200)->nullable();
            $table->boolean('no_further_rights')->default(false);
            // Signatures
            $table->foreignId('supervisor_signed_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->date('supervisor_signed_at')->nullable();
            $table->foreignId('committee_signed_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->date('committee_signed_at')->nullable();
            $table->foreignId('head_office_signed_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->date('head_office_signed_at')->nullable();
            $table->foreignId('hr_signed_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->date('hr_signed_at')->nullable();
            $table->foreignId('director_signed_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->date('director_signed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clearance_forms');
        Schema::dropIfExists('exit_interviews');
        Schema::dropIfExists('employee_onboarding');
        Schema::dropIfExists('onboarding_checklist_items');
    }
};

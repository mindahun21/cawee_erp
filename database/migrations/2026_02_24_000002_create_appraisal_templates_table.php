<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Template (Employee form vs Supervisor form)
        Schema::create('appraisal_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->enum('type', ['Employee', 'Supervisor'])->default('Employee');
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Sections within a template (I. INDIVIDUAL, II. TASK EFFECTIVENESS, etc.)
        Schema::create('appraisal_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('appraisal_templates')->cascadeOnDelete();
            $table->string('title', 150);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Criteria / factors within a section
        Schema::create('appraisal_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('appraisal_sections')->cascadeOnDelete();
            $table->string('factor_name', 200);
            $table->text('description')->nullable();
            $table->decimal('weight', 5, 2)->default(1.00); // For weighted scoring
            $table->unsignedSmallInteger('max_score')->default(5);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Completed appraisal header
        Schema::create('appraisals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('appraisal_templates');
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('evaluator_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('hr_projects')->nullOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->date('review_date')->nullable();
            $table->decimal('cumulative_average', 5, 2)->nullable();
            $table->text('general_comments')->nullable();
            $table->enum('status', ['Draft', 'Submitted', 'Reviewed', 'Approved'])->default('Draft');
            // Approval chain
            $table->foreignId('supervisor_approved_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamp('supervisor_approved_at')->nullable();
            $table->foreignId('hr_approved_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamp('hr_approved_at')->nullable();
            $table->foreignId('director_approved_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamp('director_approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Scores per criterion
        Schema::create('appraisal_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appraisal_id')->constrained('appraisals')->cascadeOnDelete();
            $table->foreignId('criterion_id')->constrained('appraisal_criteria')->cascadeOnDelete();
            $table->decimal('score', 4, 2)->nullable();
            $table->text('comments')->nullable();
            $table->timestamps();

            $table->unique(['appraisal_id', 'criterion_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appraisal_scores');
        Schema::dropIfExists('appraisals');
        Schema::dropIfExists('appraisal_criteria');
        Schema::dropIfExists('appraisal_sections');
        Schema::dropIfExists('appraisal_templates');
    }
};

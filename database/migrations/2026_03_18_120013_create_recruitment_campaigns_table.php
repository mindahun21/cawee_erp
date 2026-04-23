<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment_campaigns', function (Blueprint $table) {
            $table->id();

            $table->foreignId('job_position_id')
                  ->constrained('hr_job_positions')
                  ->cascadeOnDelete();
                  
            $table->foreignId('approval_workflow_id')
                  ->nullable()
                  ->constrained('recruitment_approval_workflows')
                  ->nullOnDelete();

            $table->foreignId('channel_id')
                  ->constrained('recruitment_channels')
                  ->restrictOnDelete();

            $table->foreignId('recruitment_plan_id')
                  ->nullable()
                  ->constrained('recruitment_plans')
                  ->nullOnDelete();

            $table->string('campaign_code')->unique()->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('requirements')->nullable();
            $table->string('location')->nullable();
            $table->string('employment_type')->default('full_time');
            $table->unsignedSmallInteger('vacancies_needed')->default(1);

            $table->decimal('salary_min', 14, 2)->nullable();
            $table->decimal('salary_max', 14, 2)->nullable();
            $table->string('currency', 10)->default('USD');
            $table->boolean('display_salary')->default(false);

            $table->string('status')->default('draft');
            $table->boolean('is_public')->default(true);

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            $table->text('reason_for_recruitment')->nullable();

            $table->foreignId('manager_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();

            $table->foreignId('created_by')->constrained('users');
            $table->text('notes')->nullable();

            // Candidate requirement filters
            $table->unsignedTinyInteger('candidate_age_from')->nullable();
            $table->unsignedTinyInteger('candidate_age_to')->nullable();
            $table->string('candidate_gender', 20)->nullable();
            $table->decimal('candidate_height_min', 4, 2)->nullable();
            $table->decimal('candidate_weight_min', 5, 2)->nullable();
            $table->string('candidate_literacy', 100)->nullable();
            $table->string('candidate_seniority', 100)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'is_public']);
            $table->index('job_position_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment_campaigns');
    }
};

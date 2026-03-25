<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('recruitment_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained('hr_departments')->cascadeOnDelete();
            $table->foreignId('job_position_id')->constrained('hr_job_positions')->cascadeOnDelete();
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('vacancies_needed')->default(1);

            $table->string('working_from')->nullable();       // Internship, Full-Time, Part-Time, Contract, Temporary
            $table->string('workplace')->nullable();           // free-text location / site

            $table->decimal('salary_from', 14, 2)->nullable();
            $table->decimal('salary_to', 14, 2)->nullable();
            $table->string('salary_currency', 10)->default('ETB'); // ETB, USD, EUR

            $table->date('start_date');                        // recruitment window start (>= today at creation)
            $table->date('end_date');                          // recruitment window end   (> start_date)

            $table->decimal('budget', 14, 2)->nullable();
            $table->longText('reason')->nullable();            // rich-editor: reason for the recruitment
            $table->longText('job_description')->nullable();   // rich-editor

            $table->foreignId('approval_workflow_id')
                ->nullable()
                ->constrained('recruitment_approval_workflows')
                ->nullOnDelete();

            $table->string('status')->default('Draft');        // Draft | Submitted | Approved | Rejected | Closed
            $table->foreignId('created_by')->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['department_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recruitment_plans');
    }
};

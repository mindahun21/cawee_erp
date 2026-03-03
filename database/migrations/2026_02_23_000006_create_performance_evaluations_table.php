<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('evaluator_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('hr_projects')->nullOnDelete();
            $table->date('review_period_start');
            $table->date('review_period_end');

            // Scored criteria (1–5 typically)
            $table->tinyInteger('effort_initiative')->default(0);
            $table->tinyInteger('technical_competence')->default(0);
            $table->tinyInteger('teamwork')->default(0);
            $table->tinyInteger('dependability')->default(0);
            $table->tinyInteger('planning_organizing')->default(0);
            $table->tinyInteger('quality_quantity')->default(0);
            $table->tinyInteger('priority_setting')->default(0);
            $table->tinyInteger('compliance')->default(0);
            $table->tinyInteger('written_communication')->default(0);
            $table->tinyInteger('coordination_collaboration')->default(0);

            $table->decimal('cumulative_average', 3, 2)->default(0);
            $table->text('general_comments')->nullable();
            $table->date('evaluation_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_evaluations');
    }
};

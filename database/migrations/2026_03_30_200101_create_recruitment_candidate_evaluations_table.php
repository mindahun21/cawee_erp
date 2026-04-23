<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment_candidate_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained('recruitment_interview_schedules')->cascadeOnDelete();
            $table->foreignId('candidate_id')->constrained('recruitment_candidates')->cascadeOnDelete();
            $table->foreignId('interviewer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('template_id')->constrained('recruitment_evaluation_form_templates');
            
            $table->decimal('overall_score', 8, 2)->nullable();
            $table->text('comments')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // An interviewer can only evaluate a specific candidate on a specific schedule once.
            $table->unique(['schedule_id', 'candidate_id', 'interviewer_id'], 'eval_sched_cand_int_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment_candidate_evaluations');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment_evaluation_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_id')->constrained('recruitment_candidate_evaluations')->cascadeOnDelete();
            $table->foreignId('criteria_id')->constrained('recruitment_evaluation_criterias')->cascadeOnDelete();
            $table->integer('score');
            $table->text('comment')->nullable();
            $table->timestamps();
            
            // Target criteria mapped once per evaluation session
            $table->unique(['evaluation_id', 'criteria_id'], 'eval_score_crit_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment_evaluation_scores');
    }
};

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
        Schema::create('recruitment_evaluation_forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('interview_id')->constrained('recruitment_interviews')->cascadeOnDelete();
            $table->foreignId('evaluator_id')->constrained('users')->cascadeOnDelete(); // panelist
            $table->decimal('total_score', 5, 2)->nullable();
            $table->string('recommendation')->nullable(); // hire | hold | reject
            $table->text('comments')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['interview_id', 'evaluator_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recruitment_evaluation_forms');
    }
};

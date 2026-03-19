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
        Schema::create('recruitment_candidate_skills', function (Blueprint $table) {
            $table->foreignId('candidate_id')->constrained('recruitment_candidates')->cascadeOnDelete();
            $table->foreignId('recruitment_skill_id')->constrained('recruitment_skills')->cascadeOnDelete();
            $table->unsignedTinyInteger('proficiency')->nullable();
            $table->timestamps();

            $table->primary(['candidate_id', 'recruitment_skill_id'], 'candidate_skill_primary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recruitment_candidate_skills');
    }
};

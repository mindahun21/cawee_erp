<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment_candidate_skill', function (Blueprint $table) {
            $table->foreignId('candidate_id')
                  ->constrained('recruitment_candidates')
                  ->cascadeOnDelete();
            $table->foreignId('recruitment_skill_id')
                  ->constrained('recruitment_skills')
                  ->restrictOnDelete();

            $table->unsignedTinyInteger('proficiency')->nullable();

            $table->primary(
                ['candidate_id', 'recruitment_skill_id'],
                'cand_skill_primary'
            );
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment_candidate_skill');
    }
};

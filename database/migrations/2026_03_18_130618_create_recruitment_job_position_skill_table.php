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
        Schema::create('recruitment_job_position_skills', function (Blueprint $table) {
            $table->foreignId('job_position_id')->constrained('hr_job_positions')->cascadeOnDelete();
            $table->foreignId('recruitment_skill_id')->constrained('recruitment_skills')->cascadeOnDelete();
            $table->boolean('is_required')->default(false);
            $table->string('min_proficiency')->nullable();
            $table->timestamps();

            $table->primary(['job_position_id', 'recruitment_skill_id'], 'job_pos_skill_primary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recruitment_job_position_skills');
    }
};

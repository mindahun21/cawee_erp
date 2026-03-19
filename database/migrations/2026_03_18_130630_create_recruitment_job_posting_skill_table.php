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
        Schema::create('recruitment_job_posting_skills', function (Blueprint $table) {
            $table->foreignId('job_posting_id')->constrained('recruitment_job_postings')->cascadeOnDelete();
            $table->foreignId('recruitment_skill_id')->constrained('recruitment_skills')->cascadeOnDelete();
            $table->boolean('is_required')->default(false);
            $table->string('min_proficiency')->nullable();
            $table->timestamps();

            $table->primary(['job_posting_id', 'recruitment_skill_id'], 'job_post_skill_primary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recruitment_job_posting_skills');
    }
};

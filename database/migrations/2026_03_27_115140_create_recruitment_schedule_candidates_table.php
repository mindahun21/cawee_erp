<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment_schedule_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained('recruitment_interview_schedules')->cascadeOnDelete();
            $table->foreignId('candidate_id')->constrained('recruitment_candidates')->cascadeOnDelete();
            $table->time('candidate_from_time')->nullable();
            $table->time('candidate_to_time')->nullable();
            $table->timestamps();

            $table->unique(['schedule_id', 'candidate_id']);
            $table->index('candidate_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment_schedule_candidates');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment_schedule_interviewers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained('recruitment_interview_schedules')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role')->default('interviewer'); // chair | interviewer | observer
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['schedule_id', 'user_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment_schedule_interviewers');
    }
};

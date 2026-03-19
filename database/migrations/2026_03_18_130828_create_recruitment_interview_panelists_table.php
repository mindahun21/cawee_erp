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
        Schema::create('recruitment_interview_panelists', function (Blueprint $table) {
            $table->foreignId('interview_id')->constrained('recruitment_interviews')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role')->default('interviewer'); // interviewer | chair | observer
            $table->primary(['interview_id', 'user_id'], 'interview_panelist_primary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recruitment_interview_panelists');
    }
};

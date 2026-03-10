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
        Schema::create('interview_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recruitment_interview_id')
                ->constrained('recruitment_interviews')
                ->cascadeOnDelete();
            $table->foreignId('added_by')
                ->nullable()
                ->constrained('users');
            $table->foreignId('candidate_id')
                ->constrained('recruitment_candidates') 
                ->cascadeOnDelete();
            $table->time('from_hour');
            $table->time('to_hour');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interview_candidates');
    }
};

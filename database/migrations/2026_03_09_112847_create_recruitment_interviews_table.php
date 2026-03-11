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
        Schema::create('recruitment_interviews', function (Blueprint $table) {
            $table->id();
            $table->string('schedule_name');
            $table->foreignId('recruitment_campaign_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('recruitment_position_id')->nullable()->constrained()->cascadeOnDelete();

            $table->date('interview_date');
            $table->time('from_hour');
            $table->time('to_hour');

            $table->string('location')->nullable();
            $table->foreignId('interviewer_id')->nullable()->constrained('users');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recruitment_interviews');
    }
};

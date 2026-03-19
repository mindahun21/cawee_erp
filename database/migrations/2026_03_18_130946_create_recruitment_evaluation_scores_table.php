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
        Schema::create('recruitment_evaluation_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('recruitment_evaluation_forms')->cascadeOnDelete();
            $table->foreignId('criteria_id')->constrained('recruitment_evaluation_criterias')->cascadeOnDelete();
            $table->unsignedTinyInteger('score');
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['form_id', 'criteria_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recruitment_evaluation_scores');
    }
};

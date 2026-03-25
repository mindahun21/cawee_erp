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
        Schema::create('recruitment_candidate_literacies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')
                  ->constrained('recruitment_candidates')
                  ->cascadeOnDelete();
            $table->date('from_date')->nullable();
            $table->date('to_date')->nullable();
            $table->string('diploma', 255)->nullable();
            $table->string('training_places', 255)->nullable();
            $table->string('specialized', 255)->nullable();
            $table->decimal('percentage', 5, 2)->nullable();
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recruitment_candidate_literacies');
    }
};

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
        Schema::create('recruitment_candidate_references', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')
                  ->constrained('recruitment_candidates')
                  ->cascadeOnDelete();
            $table->string('relationship', 100)->nullable();
            $table->string('name', 255)->nullable();
            $table->date('birthday')->nullable();
            $table->string('job', 255)->nullable();
            $table->text('address')->nullable();
            $table->string('phone', 30)->nullable();
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recruitment_candidate_references');
    }
};

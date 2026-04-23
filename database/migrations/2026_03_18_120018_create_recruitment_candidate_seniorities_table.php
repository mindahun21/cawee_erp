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
        Schema::create('recruitment_candidate_seniorities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')
                  ->constrained('recruitment_candidates')
                  ->cascadeOnDelete();
            $table->date('from_date')->nullable();
            $table->date('to_date')->nullable();     // null = current job
            $table->string('company', 255)->nullable();
            $table->string('position', 255)->nullable();
            $table->string('contact_person', 255)->nullable();
            $table->decimal('salary', 14, 2)->nullable();
            $table->text('reason_for_leaving')->nullable();
            $table->text('job_description')->nullable();
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recruitment_candidate_seniorities');
    }
};

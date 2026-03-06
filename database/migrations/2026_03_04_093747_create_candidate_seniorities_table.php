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
        Schema::create('candidate_seniorities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')
                ->constrained('recruitment_candidates')
                ->cascadeOnDelete();
            $table->date('from_date')->nullable();
            $table->date('to_date')->nullable();
            $table->string('company')->nullable();
            $table->string('position')->nullable();
            $table->string('contact_person')->nullable();
            $table->decimal('salary', 10, 2)->nullable();
            $table->string('reason_for_leaving')->nullable();
            $table->text('job_description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidate_seniorities');
    }
};

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
        Schema::create('recruitment_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained('hr_departments')->cascadeOnDelete();
            $table->foreignId('job_position_id')->constrained('hr_job_positions')->cascadeOnDelete();
            $table->unsignedTinyInteger('vacancies_needed');
            $table->date('expected_hire_date');
            $table->decimal('budget', 14, 2)->nullable();
            $table->string('status')->default('draft');   // draft | approved | closed
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['department_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recruitment_plans');
    }
};

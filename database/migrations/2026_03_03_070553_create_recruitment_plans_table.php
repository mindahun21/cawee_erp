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
            $table->string('plan_name');
            $table->string('position');
            $table->string('department')->nullable();
            $table->integer('quantity')->nullable();
            $table->string('working_form')->nullable();
            $table->string('workplace')->nullable();
            $table->decimal('starting_salary_from', 10, 2)->nullable();
            $table->decimal('starting_salary_to', 10, 2)->nullable();
            $table->date('from_date')->nullable();
            $table->date('to_date')->nullable();
            $table->text('reason')->nullable();
            $table->text('job_description')->nullable();
            $table->foreignId('approver_id')->constrained('users')->cascadeOnDelete();
            $table->integer('age_from')->nullable();
            $table->integer('age_to')->nullable();
            $table->string('gender')->nullable();
            $table->decimal('height', 4, 2)->nullable();
            $table->decimal('weight', 5, 2)->nullable();
            $table->string('literacy')->nullable();
            $table->string('seniority')->nullable();
            $table->string('attachment')->nullable();
            $table->timestamps();
            $table->softDeletes();
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

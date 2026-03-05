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
        Schema::create('recruitment_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('campaign_code');
            $table->string('campaign_name');
            $table->foreignId('recruitment_plans')->nullable()->constrained('plan_names')->cascadeOnDelete();
            $table->foreignId('recruitment_channel')->constrained('names')->cascadeOnDelete();
            $table->string('position');
            $table->string('company')->nullable();
            $table->integer('quantity')->nullable();
            $table->string('working_form')->nullable();
            $table->string('department')->nullable();
            $table->string('workplace')->nullable();
            $table->decimal('starting_salary_from', 10, 2)->nullable();
            $table->decimal('starting_salary_to', 10, 2)->nullable();
            $table->boolean('display_salary')->default(true);
            $table->date('from_date')->nullable();
            $table->date('to_date')->nullable();
            $table->text('reason')->nullable();
            $table->text('job_description')->nullable();
            $table->foreignId('manager_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('follower_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();

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
        Schema::dropIfExists('recruitment_campaigns');
    }
};

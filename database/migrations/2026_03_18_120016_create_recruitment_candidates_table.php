<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment_candidates', function (Blueprint $table) {
            $table->id();

            // Identity & linking
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->string('candidate_code')->unique();

            // General info
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->date('birthday')->nullable();
            $table->string('gender', 20)->nullable();
            $table->decimal('desired_salary', 14, 2)->nullable();
            $table->string('currency', 10)->nullable();
            $table->text('birthplace')->nullable();
            $table->text('home_town')->nullable();
            $table->string('identification', 100)->nullable();
            $table->date('days_for_identity')->nullable();
            $table->string('place_of_issue', 255)->nullable();
            $table->string('marital_status', 50)->nullable();
            $table->string('nationality', 100)->nullable();
            $table->string('nation', 100)->nullable();
            $table->string('religion', 100)->nullable();
            $table->decimal('height_m', 4, 2)->nullable();
            $table->decimal('weight_kg', 5, 2)->nullable();
            $table->text('introduce_yourself')->nullable();

            // Contact info
            $table->string('phone', 30)->nullable();
            $table->string('alternate_phone', 30)->nullable();
            $table->string('email')->unique();
            $table->string('skype', 100)->nullable();
            $table->string('facebook', 255)->nullable();
            $table->string('linkedin_url', 255)->nullable();
            $table->text('resident')->nullable();
            $table->text('current_accommodation')->nullable();

            // Media
            $table->string('photo_path', 500)->nullable();
            $table->string('resume_path', 500)->nullable();

            // Skills summary
            $table->string('seniority', 50)->nullable();
            $table->text('interests')->nullable();
            $table->json('skills_snapshot')->nullable();

            // Portal access
            $table->string('password', 255)->nullable();
            $table->boolean('portal_access')->default(false);
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();

            $table->timestamps();
            $table->softDeletes();

            $table->index('email');
            $table->index('candidate_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment_candidates');
    }
};

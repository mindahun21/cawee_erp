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
        Schema::create('recruitment_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recruitment_campaign_id')->nullable()->constrained('recruitment_campaigns')->cascadeOnDelete();
            $table->string('candidate_code');
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->date('birthday')->nullable();
            $table->string('gender')->nullable();
            $table->string('desired_salary')->nullable();
            $table->text('birthplace')->nullable();
            $table->text('hometown')->nullable();
            $table->string('identification')->nullable();
            $table->string('place_of_issue')->nullable();
            $table->date('days_for_identity')->nullable();
            $table->string('maritial_status')->nullable();
            $table->string('nationality')->nullable();
            $table->string('nation')->nullable();
            $table->string('religion')->nullable();
            $table->string('height')->nullable();
            $table->string('weight')->nullable();
            $table->text('introduction')->nullable();
            $table->string('seniority')->nullable();
            $table->text('interests')->nullable();
            $table->integer('test')->nullable();
            $table->string('notes')->nullable();
            $table->foreignId('recruitment_skill_id')->nullable()->constrained('recruitment_skills')->cascadeOnDelete();

            $table->string('contact_phone')->nullable();
            $table->string('contact_phone_alternative')->nullable();
            $table->string('contact_email');
            $table->string('contact_skype')->nullable();
            $table->string('contact_facebook')->nullable();
            $table->string('contact_linkedin')->nullable();
            $table->string('contact_resident')->nullable();
            $table->string('contact_accomodationn')->nullable();
            $table->string('contact_password')->nullable();
            $table->string('profile_picture')->nullable();
            $table->string('cv')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recruitment_candidates');
    }
};

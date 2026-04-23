<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1.2 — Add soft deletes to models that are missing them.
 *
 * RecruitmentOffer, RecruitmentInterviewSchedule, and
 * RecruitmentChannel currently lack SoftDeletes, creating an
 * inconsistent audit trail compared to other recruitment models.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recruitment_offers', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('recruitment_interview_schedules', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('recruitment_channels', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('recruitment_offers', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('recruitment_interview_schedules', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('recruitment_channels', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};

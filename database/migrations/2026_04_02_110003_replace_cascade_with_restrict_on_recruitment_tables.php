<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1.3 — Replace dangerous cascadeOnDelete with restrictOnDelete.
 *
 * Deleting a department or job position currently cascades into
 * deleting all recruitment plans and campaigns, which could silently
 * destroy critical business records.  Using restrictOnDelete forces
 * the user to handle dependent records first.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── recruitment_plans ──────────────────────────────────
        Schema::table('recruitment_plans', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropForeign(['job_position_id']);
        });

        Schema::table('recruitment_plans', function (Blueprint $table) {
            $table->foreign('department_id')
                  ->references('id')->on('hr_departments')
                  ->restrictOnDelete();

            $table->foreign('job_position_id')
                  ->references('id')->on('hr_job_positions')
                  ->restrictOnDelete();
        });

        // ── recruitment_campaigns ──────────────────────────────
        Schema::table('recruitment_campaigns', function (Blueprint $table) {
            $table->dropForeign(['job_position_id']);
        });

        Schema::table('recruitment_campaigns', function (Blueprint $table) {
            $table->foreign('job_position_id')
                  ->references('id')->on('hr_job_positions')
                  ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        // ── recruitment_plans ──────────────────────────────────
        Schema::table('recruitment_plans', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropForeign(['job_position_id']);
        });

        Schema::table('recruitment_plans', function (Blueprint $table) {
            $table->foreign('department_id')
                  ->references('id')->on('hr_departments')
                  ->cascadeOnDelete();

            $table->foreign('job_position_id')
                  ->references('id')->on('hr_job_positions')
                  ->cascadeOnDelete();
        });

        // ── recruitment_campaigns ──────────────────────────────
        Schema::table('recruitment_campaigns', function (Blueprint $table) {
            $table->dropForeign(['job_position_id']);
        });

        Schema::table('recruitment_campaigns', function (Blueprint $table) {
            $table->foreign('job_position_id')
                  ->references('id')->on('hr_job_positions')
                  ->cascadeOnDelete();
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * HR Settings / Lookup Tables
 *
 * These tables store managed values that admins configure in the Settings
 * section. Employee records then reference them via foreign keys instead of
 * storing raw free-text, giving consistent reporting and filtering.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Departments ──────────────────────────────────────────────
        Schema::create('hr_departments', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150)->unique();
            $table->string('code', 20)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // ── Job Positions ────────────────────────────────────────────
        Schema::create('hr_job_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->nullable()->constrained('hr_departments')->nullOnDelete();
            $table->string('title', 150)->unique();
            $table->string('grade', 20)->nullable();
            $table->timestamps();
        });

        // ── Contract Types ───────────────────────────────────────────
        Schema::create('hr_contract_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ── Education Levels ─────────────────────────────────────────
        Schema::create('hr_education_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // ── Fields of Study ──────────────────────────────────────────
        Schema::create('hr_fields_of_study', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150)->unique();
            $table->timestamps();
        });

        // ── Training Types ───────────────────────────────────────────
        Schema::create('hr_training_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->timestamps();
        });

        // ── Allowance Types ──────────────────────────────────────────
        Schema::create('hr_allowance_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->boolean('is_taxable')->default(false);
            $table->timestamps();
        });

        // ── Update employees: add FK columns ─────────────────────────
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->constrained('hr_departments')->nullOnDelete()->after('position');
            $table->foreignId('job_position_id')->nullable()->constrained('hr_job_positions')->nullOnDelete()->after('department_id');
            $table->foreignId('contract_type_id')->nullable()->constrained('hr_contract_types')->nullOnDelete()->after('employment_type');
            $table->foreignId('education_level_id')->nullable()->constrained('hr_education_levels')->nullOnDelete()->after('education_level');
            $table->foreignId('field_of_study_id')->nullable()->constrained('hr_fields_of_study')->nullOnDelete()->after('field_of_study');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
            $table->dropConstrainedForeignId('job_position_id');
            $table->dropConstrainedForeignId('contract_type_id');
            $table->dropConstrainedForeignId('education_level_id');
            $table->dropConstrainedForeignId('field_of_study_id');
        });

        Schema::dropIfExists('hr_allowance_types');
        Schema::dropIfExists('hr_training_types');
        Schema::dropIfExists('hr_fields_of_study');
        Schema::dropIfExists('hr_education_levels');
        Schema::dropIfExists('hr_contract_types');
        Schema::dropIfExists('hr_job_positions');
        Schema::dropIfExists('hr_departments');
    }
};

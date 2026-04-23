<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the full Beneficiary Registry tables for the M&E module.
 * Includes: households, beneficiaries, baseline_assessments,
 *           beneficiary_enrollments, case_notes, referrals.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Households ────────────────────────────────────────────────────
        Schema::create('me_households', function (Blueprint $table): void {
            $table->id();
            $table->string('household_code', 30)->unique();
            $table->foreignId('project_id')->nullable()->constrained('me_projects')->nullOnDelete();
            $table->string('head_of_household', 100)->nullable();
            $table->unsignedSmallInteger('family_size')->default(1);
            $table->enum('vulnerability_status', ['low', 'medium', 'high', 'critical'])->default('low');
            $table->enum('income_level', ['none', 'very_low', 'low', 'medium', 'high'])->default('none');
            $table->text('address')->nullable();
            $table->string('kebele', 100)->nullable();
            $table->string('woreda', 100)->nullable();
            $table->string('zone', 100)->nullable();
            $table->string('region', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // ── 2. Beneficiaries ─────────────────────────────────────────────────
        Schema::create('me_beneficiaries', function (Blueprint $table): void {
            $table->id();
            $table->string('beneficiary_code', 30)->unique();
            $table->foreignId('household_id')->nullable()->constrained('me_households')->nullOnDelete();
            $table->string('full_name', 150);
            $table->string('full_name_local', 150)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->unsignedSmallInteger('age')->nullable();
            $table->enum('gender', ['male', 'female', 'other', 'prefer_not_to_say'])->default('male');
            $table->string('national_id', 60)->nullable()->unique();
            $table->string('phone', 30)->nullable();
            $table->text('address')->nullable();
            $table->string('kebele', 100)->nullable();
            $table->string('woreda', 100)->nullable();
            $table->string('zone', 100)->nullable();
            $table->string('region', 100)->nullable();
            $table->string('photo_path', 255)->nullable();
            $table->enum('disability_status', ['none', 'physical', 'visual', 'hearing', 'cognitive', 'multiple'])->default('none');
            $table->enum('status', ['active', 'inactive', 'graduated', 'suspended', 'deceased'])->default('active');
            $table->date('registered_at')->useCurrent();
            $table->foreignId('registered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // ── 3. Baseline Assessments ───────────────────────────────────────────
        Schema::create('me_baseline_assessments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('beneficiary_id')->constrained('me_beneficiaries')->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('me_projects')->nullOnDelete();
            $table->enum('education_level', ['none', 'primary', 'secondary', 'tertiary', 'vocational'])->nullable();
            $table->text('health_status')->nullable();
            $table->enum('nutrition_status', ['normal', 'moderate_malnutrition', 'severe_malnutrition'])->default('normal');
            $table->text('livelihood_info')->nullable();
            $table->decimal('monthly_income', 12, 2)->default(0.00);
            $table->text('assets')->nullable();
            $table->text('shelter_condition')->nullable();
            $table->text('water_sanitation')->nullable();
            $table->date('assessment_date');
            $table->foreignId('assessed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // ── 4. Beneficiary Enrollments (project participation) ─────────────────
        Schema::create('me_beneficiary_enrollments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('beneficiary_id')->constrained('me_beneficiaries')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('me_projects')->cascadeOnDelete();
            $table->foreignId('reporting_period_id')->nullable()->constrained('me_reporting_periods')->nullOnDelete();
            $table->date('enrollment_date');
            $table->date('exit_date')->nullable();
            $table->enum('participation_status', ['enrolled', 'active', 'completed', 'dropped_out', 'suspended'])->default('enrolled');
            $table->string('exit_reason', 255)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['beneficiary_id', 'project_id'], 'unique_beneficiary_project');
        });

        // ── 5. Case Notes ─────────────────────────────────────────────────────
        Schema::create('me_case_notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('beneficiary_id')->constrained('me_beneficiaries')->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('me_projects')->nullOnDelete();
            $table->foreignId('authored_by')->constrained('users')->cascadeOnDelete();
            $table->enum('note_type', ['general', 'follow_up', 'counseling', 'incident', 'assessment', 'home_visit'])->default('general');
            $table->text('content');
            $table->date('follow_up_date')->nullable();
            $table->boolean('is_confidential')->default(false);
            $table->timestamps();
        });

        // ── 6. Referrals ──────────────────────────────────────────────────────
        Schema::create('me_referrals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('beneficiary_id')->constrained('me_beneficiaries')->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('me_projects')->nullOnDelete();
            $table->foreignId('referred_by')->constrained('users')->cascadeOnDelete();
            $table->enum('referral_type', ['health', 'psychosocial', 'legal', 'education', 'livelihood', 'shelter', 'protection', 'other']);
            $table->string('referred_to', 200);
            $table->text('reason');
            $table->date('referral_date');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->text('outcome')->nullable();
            $table->date('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('me_referrals');
        Schema::dropIfExists('me_case_notes');
        Schema::dropIfExists('me_beneficiary_enrollments');
        Schema::dropIfExists('me_baseline_assessments');
        Schema::dropIfExists('me_beneficiaries');
        Schema::dropIfExists('me_households');
    }
};

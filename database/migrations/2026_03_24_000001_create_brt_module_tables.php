<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the Beneficiary Registry & Project Tracking (BRT) extended tables.
 *
 * The core tables (me_beneficiaries, me_households, me_projects, me_case_notes,
 * me_referrals, me_baseline_assessments, me_beneficiary_enrollments) are reused
 * from the M&E schema. This migration adds the ADDITIONAL tables required by the
 * BRT feature specification:
 *
 *  – brt_training_events   : training / community events
 *  – brt_attendance        : per-beneficiary attendance at events
 *  – brt_progress_updates  : periodic progress monitoring updates
 *  – me_projects extended  : budget, status, donor, type, location fields
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Extend me_projects with full project-tracking fields ───────────
        Schema::table('me_projects', function (Blueprint $table): void {
            if (! Schema::hasColumn('me_projects', 'status')) {
                $table->enum('status', ['planning', 'active', 'on_hold', 'completed', 'cancelled'])
                    ->default('planning')
                    ->after('end_date');
            }
            if (! Schema::hasColumn('me_projects', 'project_type')) {
                $table->string('project_type', 80)->nullable()->after('status');
            }
            if (! Schema::hasColumn('me_projects', 'donor')) {
                $table->string('donor', 150)->nullable()->after('project_type');
            }
            if (! Schema::hasColumn('me_projects', 'budget')) {
                $table->decimal('budget', 18, 2)->nullable()->after('donor');
            }
            if (! Schema::hasColumn('me_projects', 'budget_currency')) {
                $table->string('budget_currency', 10)->default('ETB')->after('budget');
            }
            if (! Schema::hasColumn('me_projects', 'implementing_org')) {
                $table->string('implementing_org', 200)->nullable()->after('budget_currency');
            }
            if (! Schema::hasColumn('me_projects', 'target_beneficiaries')) {
                $table->unsignedInteger('target_beneficiaries')->default(0)->after('implementing_org');
            }
            if (! Schema::hasColumn('me_projects', 'location')) {
                $table->string('location', 255)->nullable()->after('target_beneficiaries');
            }
            if (! Schema::hasColumn('me_projects', 'manager_id')) {
                $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete()->after('location');
            }
            if (! Schema::hasColumn('me_projects', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // ── 2. Extend me_beneficiaries with child-specific ID columns ─────────
        Schema::table('me_beneficiaries', function (Blueprint $table): void {
            if (! Schema::hasColumn('me_beneficiaries', 'father_name')) {
                $table->string('father_name', 100)->nullable()->after('full_name');
            }
            if (! Schema::hasColumn('me_beneficiaries', 'grandfather_name')) {
                $table->string('grandfather_name', 100)->nullable()->after('father_name');
            }
            if (! Schema::hasColumn('me_beneficiaries', 'child_code')) {
                $table->string('child_code', 30)->nullable()->unique()->after('beneficiary_code');
            }
        });

        // ── 3. Training Events ────────────────────────────────────────────────
        Schema::create('brt_training_events', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 200);
            $table->string('event_code', 40)->unique()->nullable();
            $table->foreignId('project_id')->nullable()->constrained('me_projects')->nullOnDelete();
            $table->enum('event_type', [
                'training', 'workshop', 'community_meeting',
                'awareness_campaign', 'support_group', 'iga_session', 'other',
            ])->default('training');
            $table->date('event_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('venue', 200)->nullable();
            $table->string('facilitator', 150)->nullable();
            $table->text('objectives')->nullable();
            $table->text('topics_covered')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        // ── 4. Training / Event Attendance ────────────────────────────────────
        Schema::create('brt_attendance', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_id')->constrained('brt_training_events')->cascadeOnDelete();
            $table->foreignId('beneficiary_id')->constrained('me_beneficiaries')->cascadeOnDelete();
            $table->enum('attendance_status', ['present', 'absent', 'excused', 'late'])->default('present');
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->unique(['event_id', 'beneficiary_id'], 'unique_event_beneficiary');
        });

        // ── 5. Progress Updates (periodic monitoring) ─────────────────────────
        Schema::create('brt_progress_updates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('beneficiary_id')->constrained('me_beneficiaries')->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('me_projects')->nullOnDelete();
            $table->foreignId('authored_by')->constrained('users')->cascadeOnDelete();
            $table->date('update_date');
            $table->enum('update_type', [
                'routine_monitoring', 'milestone_review', 'exit_assessment',
                'home_visit', 'phone_checkin', 'other',
            ])->default('routine_monitoring');
            $table->enum('overall_progress', ['improving', 'stable', 'declining', 'unknown'])->default('stable');
            $table->text('summary');
            $table->text('challenges')->nullable();
            $table->text('recommendations')->nullable();
            $table->date('next_update_due')->nullable();
            $table->boolean('high_risk_flag')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brt_progress_updates');
        Schema::dropIfExists('brt_attendance');
        Schema::dropIfExists('brt_training_events');

        Schema::table('me_beneficiaries', function (Blueprint $table): void {
            $columnsToDrop = [];

            foreach (['father_name', 'grandfather_name', 'child_code'] as $column) {
                if (Schema::hasColumn('me_beneficiaries', $column)) {
                    $columnsToDrop[] = $column;
                }
            }

            if ($columnsToDrop !== []) {
                $table->dropColumn($columnsToDrop);
            }
        });

        Schema::table('me_projects', function (Blueprint $table): void {
            if (Schema::hasColumn('me_projects', 'manager_id')) {
                $table->dropConstrainedForeignId('manager_id');
            }

            $columnsToDrop = [];
            foreach ([
                'status',
                'project_type',
                'donor',
                'budget',
                'budget_currency',
                'implementing_org',
                'target_beneficiaries',
                'location',
                'deleted_at',
            ] as $column) {
                if (Schema::hasColumn('me_projects', $column)) {
                    $columnsToDrop[] = $column;
                }
            }

            if ($columnsToDrop !== []) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};

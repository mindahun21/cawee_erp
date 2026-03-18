<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Rebuild Leave System — clean slate.
 *
 * Drops the partial/inconsistent leave tables built by previous migrations
 * and replaces them with a complete, production-ready schema that mirrors
 * the Ethiopian Labour Proclamation rules used in the existing Java system.
 *
 * Tables created / replaced:
 *  - hr_leave_policies   (configurable accrual settings per organisation)
 *  - hr_leave_types      (full flags: is_annual, is_working_days, is_paid …)
 *  - hr_leave_requests   (single canonical table, replaces hr_leave_requests + leave_requests)
 *  - hr_leave_imports    (tracks each "fake" request created during import)
 *
 * The old leave_requests table and leave_balances table are dropped because
 * balance is always computed on-the-fly (no stored balance == no stale data).
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Drop all old leave tables in safe order ─────────────────
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('hr_leave_requests');
        Schema::dropIfExists('hr_leave_requests_old_v1');
        Schema::dropIfExists('leave_balances');
        Schema::dropIfExists('leave_requests');

        // ── 2. Drop and recreate hr_leave_types (completely different shape) ──
        Schema::dropIfExists('hr_leave_types');
        Schema::create('hr_leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150)->unique();

            // What kind of leave this is
            $table->boolean('is_annual')->default(false)
                ->comment('Annual accruing leave — the main cumulative yearly leave');
            $table->boolean('is_paid')->default(true);
            $table->boolean('is_working_days')->default(true)
                ->comment('If true, Sundays & holidays are skipped when computing end_date');
            $table->boolean('is_hourly')->default(false)
                ->comment('Hourly leave (portion of a single day)');
            $table->boolean('is_fixed')->default(false)
                ->comment('Fixed number of days regardless of balance (e.g. maternity)');

            // Limits
            $table->unsignedSmallInteger('max_days')->default(0)
                ->comment('Max days per single request; 0 = unlimited');
            $table->unsignedSmallInteger('default_days')->default(0)
                ->comment('Auto-filled days when creating request; 0 = user enters');

            $table->boolean('requires_document')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // ── 3. Leave Policy Settings ────────────────────────────────────
        // One row per organisation (singleton). HR can configure accrual rules
        // without a code deploy.
        Schema::create('hr_leave_policies', function (Blueprint $table) {
            $table->id();

            // Ethiopian Labour Proclamation thresholds
            $table->date('era_boundary_date')->default('2019-07-08')
                ->comment('July 8 2019 GC — date when new labour rules took effect');

            // Pre-boundary era (Proclamation 377/2003: base = 14 days)
            $table->unsignedTinyInteger('pre_era_base_days')->default(14)
                ->comment('Annual leave base days before era_boundary_date');
            $table->unsignedTinyInteger('pre_era_accrual_per_year')->default(1)
                ->comment('Extra days accrued per completed service year before boundary');

            // Post-boundary era (Proclamation 1156/2019: base = 16 days)
            $table->unsignedTinyInteger('post_era_base_days')->default(16)
                ->comment('Annual leave base days on/after era_boundary_date');
            // Accrual: +1 day for every N complete years of post-era service
            $table->unsignedTinyInteger('post_era_accrual_every_n_years')->default(2)
                ->comment('Post-era accrual: +1 day per N complete service years');

            // How many past fiscal years the FIFO redistribution window covers
            $table->unsignedTinyInteger('fifo_window_years')->default(3)
                ->comment('Number of recent periods used in FIFO balance redistribution');

            // Working week
            $table->boolean('skip_sundays')->default(true)
                ->comment('Skip Sundays when computing end_date for working-day leave');
            $table->boolean('skip_public_holidays')->default(true)
                ->comment('Skip holidays from hr_holidays table when computing end_date');

            // Fiscal year boundary (Ethiopian: July 8 = Hamle 1)
            $table->string('fiscal_year_month_day', 5)->default('07-08')
                ->comment('MM-DD of annual fiscal year reset (default July 8 GC)');

            $table->timestamps();
        });

        // ── 4. Rebuild hr_leave_requests (canonical table) ──────────────
        Schema::create('hr_leave_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_id')
                ->constrained('employees')
                ->onDelete('cascade');

            $table->foreignId('hr_leave_type_id')
                ->constrained('hr_leave_types')
                ->onDelete('restrict');

            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedSmallInteger('no_of_days')->default(1);

            // Hourly leave fields
            $table->time('from_time')->nullable()
                ->comment('Start time for hourly leave');
            $table->time('to_time')->nullable()
                ->comment('End time for hourly leave');
            $table->decimal('total_hours', 4, 2)->default(0);

            $table->text('reason')->nullable();
            $table->text('remarks')->nullable();

            // Document attachment
            $table->string('supporting_document')->nullable();

            // ── Overall approval status ──────────────────────────────
            $table->enum('approval_status', ['Pending', 'Approved', 'Rejected'])
                ->default('Pending');
            $table->date('approval_date')->nullable();

            // ── Stage 1 — Supervisor ─────────────────────────────────
            $table->enum('supervisor_status', ['Pending', 'Approved', 'Rejected'])
                ->default('Pending');
            $table->foreignId('supervisor_approved_by')->nullable()
                ->constrained('employees')->nullOnDelete();
            $table->timestamp('supervisor_approved_at')->nullable();

            // ── Stage 2 — HR ─────────────────────────────────────────
            $table->enum('hr_status', ['Pending', 'Approved', 'Rejected'])
                ->default('Pending');
            $table->foreignId('hr_approved_by')->nullable()
                ->constrained('employees')->nullOnDelete();
            $table->timestamp('hr_approved_at')->nullable();

            // ── Stage 3 — Director ────────────────────────────────────
            $table->enum('director_status', ['Pending', 'Approved', 'Rejected'])
                ->default('Pending');
            $table->foreignId('director_approved_by')->nullable()
                ->constrained('employees')->nullOnDelete();
            $table->timestamp('director_approved_at')->nullable();

            // ── Import metadata ───────────────────────────────────────
            $table->boolean('is_imported')->default(false)
                ->comment('True for synthetic requests created by the leave-import tool');
            $table->unsignedSmallInteger('import_fiscal_year')->nullable()
                ->comment('Ethiopian fiscal year this imported request represents');

            $table->timestamps();
            $table->softDeletes();
        });

        // ── 5. Insert default leave policy row ─────────────────────────
        DB::table('hr_leave_policies')->insert([
            'era_boundary_date'           => '2019-07-08',
            'pre_era_base_days'           => 14,
            'pre_era_accrual_per_year'    => 1,
            'post_era_base_days'          => 16,
            'post_era_accrual_every_n_years' => 2,
            'fifo_window_years'           => 3,
            'skip_sundays'                => true,
            'skip_public_holidays'        => true,
            'fiscal_year_month_day'       => '07-08',
            'created_at'                  => now(),
            'updated_at'                  => now(),
        ]);

        // ── 6. Insert default leave types ──────────────────────────────
        $now = now();
        DB::table('hr_leave_types')->insert([
            [
                'name'             => 'Annual Leave',
                'is_annual'        => true,
                'is_paid'          => true,
                'is_working_days'  => true,
                'is_hourly'        => false,
                'is_fixed'         => false,
                'max_days'         => 0,
                'default_days'     => 0,
                'requires_document'=> false,
                'is_active'        => true,
                'description'      => 'Yearly cumulative leave entitlement per Ethiopian Labour Proclamation.',
                'created_at'       => $now,
                'updated_at'       => $now,
            ],
            [
                'name'             => 'Sick Leave',
                'is_annual'        => false,
                'is_paid'          => true,
                'is_working_days'  => true,
                'is_hourly'        => false,
                'is_fixed'         => false,
                'max_days'         => 30,
                'default_days'     => 0,
                'requires_document'=> true,
                'is_active'        => true,
                'description'      => 'Paid sick leave with medical certificate.',
                'created_at'       => $now,
                'updated_at'       => $now,
            ],
            [
                'name'             => 'Maternity Leave',
                'is_annual'        => false,
                'is_paid'          => true,
                'is_working_days'  => false,
                'is_hourly'        => false,
                'is_fixed'         => true,
                'max_days'         => 90,
                'default_days'     => 90,
                'requires_document'=> true,
                'is_active'        => true,
                'description'      => '90 consecutive calendar days of maternity leave.',
                'created_at'       => $now,
                'updated_at'       => $now,
            ],
            [
                'name'             => 'Marriage Leave',
                'is_annual'        => false,
                'is_paid'          => true,
                'is_working_days'  => true,
                'is_hourly'        => false,
                'is_fixed'         => true,
                'max_days'         => 3,
                'default_days'     => 3,
                'requires_document'=> false,
                'is_active'        => true,
                'description'      => '3 working days for marriage once in service.',
                'created_at'       => $now,
                'updated_at'       => $now,
            ],
            [
                'name'             => 'Bereavement Leave',
                'is_annual'        => false,
                'is_paid'          => true,
                'is_working_days'  => true,
                'is_hourly'        => false,
                'is_fixed'         => true,
                'max_days'         => 3,
                'default_days'     => 3,
                'requires_document'=> false,
                'is_active'        => true,
                'description'      => '3 working days for death of immediate family member.',
                'created_at'       => $now,
                'updated_at'       => $now,
            ],
            [
                'name'             => 'Unpaid Leave',
                'is_annual'        => false,
                'is_paid'          => false,
                'is_working_days'  => true,
                'is_hourly'        => false,
                'is_fixed'         => false,
                'max_days'         => 0,
                'default_days'     => 0,
                'requires_document'=> false,
                'is_active'        => true,
                'description'      => 'Leave without salary payment.',
                'created_at'       => $now,
                'updated_at'       => $now,
            ],
            [
                'name'             => 'Hourly Leave',
                'is_annual'        => false,
                'is_paid'          => true,
                'is_working_days'  => false,
                'is_hourly'        => true,
                'is_fixed'         => false,
                'max_days'         => 1,
                'default_days'     => 0,
                'requires_document'=> false,
                'is_active'        => true,
                'description'      => 'Partial-day leave measured in hours.',
                'created_at'       => $now,
                'updated_at'       => $now,
            ],
        ]);
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_leave_requests');
        Schema::dropIfExists('hr_leave_policies');
        Schema::dropIfExists('hr_leave_types');
    }
};

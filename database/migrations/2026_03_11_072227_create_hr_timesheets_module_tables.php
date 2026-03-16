<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('hr_leave_types')) {
            Schema::create('hr_leave_types', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }



        if (!Schema::hasTable('hr_timesheets')) {
            Schema::create('hr_timesheets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->foreignId('location_id')->nullable()->constrained('hr_locations')->nullOnDelete();
                $table->unsignedTinyInteger('month');
                $table->unsignedSmallInteger('year');
                $table->enum('status', ['draft', 'submitted', 'approved', 'rejected'])->default('draft');
                $table->foreignId('supervisor_id')->nullable()->constrained('employees')->nullOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('employees')->nullOnDelete();
                $table->timestamps();
                $table->unique(['employee_id', 'month', 'year']);
            });
        }

        if (!Schema::hasTable('hr_timesheet_entries')) {
            Schema::create('hr_timesheet_entries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('hr_timesheet_id')->constrained('hr_timesheets')->cascadeOnDelete();
                $table->foreignId('project_id')->constrained('hr_projects')->cascadeOnDelete();
                $table->unsignedTinyInteger('day');
                $table->decimal('hours', 4, 2);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('hr_timesheet_leaves')) {
            Schema::create('hr_timesheet_leaves', function (Blueprint $table) {
                $table->id();
                $table->foreignId('hr_timesheet_id')->constrained('hr_timesheets')->cascadeOnDelete();
                $table->foreignId('hr_leave_type_id')->constrained('hr_leave_types')->cascadeOnDelete();
                $table->unsignedTinyInteger('day');
                $table->decimal('hours', 4, 2);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_timesheet_leaves');
        Schema::dropIfExists('hr_timesheet_entries');
        Schema::dropIfExists('hr_timesheets');

        Schema::dropIfExists('hr_leave_types');
    }
};

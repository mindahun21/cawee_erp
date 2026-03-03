<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Monthly timesheet header
        Schema::create('timesheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('hr_locations')->nullOnDelete();
            $table->unsignedTinyInteger('month');     // 1-12
            $table->unsignedSmallInteger('year');
            $table->enum('status', ['Draft', 'Submitted', 'Approved', 'Rejected'])->default('Draft');
            $table->foreignId('approved_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->date('approved_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['employee_id', 'month', 'year']);
        });

        // Detail rows: one row per project per timesheet
        Schema::create('timesheet_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('timesheet_id')->constrained('timesheets')->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('hr_projects')->nullOnDelete();
            $table->string('work_site', 50)->default('Head Office'); // Head Office / Field Office
            // daily_hours: JSON array indexed 1..31
            $table->json('daily_hours')->nullable(); // {"1":8,"2":8,...}
            $table->decimal('total_hours', 6, 2)->default(0);
            $table->timestamps();
        });

        // Leave rows within a timesheet
        Schema::create('timesheet_leave_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('timesheet_id')->constrained('timesheets')->cascadeOnDelete();
            $table->enum('leave_type', ['Vacation', 'Sick', 'Holiday', 'Personal', 'Other']);
            $table->json('daily_flags')->nullable(); // {"1":1,"3":1,...}  1=absent that day
            $table->decimal('total_days', 5, 1)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timesheet_leave_rows');
        Schema::dropIfExists('timesheet_rows');
        Schema::dropIfExists('timesheets');
    }
};

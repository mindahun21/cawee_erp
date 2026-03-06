<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Per-employee leave balance tracker (auto-updated)
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->decimal('annual_entitled', 5, 1)->default(0);
            $table->decimal('annual_used', 5, 1)->default(0);
            $table->decimal('annual_balance', 5, 1)->default(0);
            $table->decimal('sick_entitled', 5, 1)->default(0);
            $table->decimal('sick_used', 5, 1)->default(0);
            $table->decimal('sick_balance', 5, 1)->default(0);
            $table->decimal('maternity_entitled', 5, 1)->default(0);
            $table->decimal('maternity_used', 5, 1)->default(0);
            $table->decimal('maternity_balance', 5, 1)->default(0);
            $table->decimal('field_entitled', 5, 1)->default(0);
            $table->decimal('field_used', 5, 1)->default(0);
            $table->decimal('field_balance', 5, 1)->default(0);
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->unique(['employee_id', 'year']);
        });

        // Upgrade leave_requests table with multi-level approval columns
        Schema::table('leave_requests', function (Blueprint $table) {
            // Supervisor approval
            $table->foreignId('supervisor_approved_by')->nullable()->constrained('employees')->nullOnDelete()->after('approved_by');
            $table->timestamp('supervisor_approved_at')->nullable()->after('supervisor_approved_by');
            $table->enum('supervisor_status', ['Pending', 'Approved', 'Rejected'])->default('Pending')->after('supervisor_approved_at');

            // HR approval
            $table->foreignId('hr_approved_by')->nullable()->constrained('employees')->nullOnDelete()->after('supervisor_status');
            $table->timestamp('hr_approved_at')->nullable()->after('hr_approved_by');
            $table->enum('hr_status', ['Pending', 'Approved', 'Rejected'])->default('Pending')->after('hr_approved_at');

            // Director approval (final)
            $table->foreignId('director_approved_by')->nullable()->constrained('employees')->nullOnDelete()->after('hr_status');
            $table->timestamp('director_approved_at')->nullable()->after('director_approved_by');

            // Calculated duration
            $table->decimal('duration_days', 5, 1)->nullable()->after('director_approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropColumn([
                'supervisor_approved_by', 'supervisor_approved_at', 'supervisor_status',
                'hr_approved_by', 'hr_approved_at', 'hr_status',
                'director_approved_by', 'director_approved_at', 'duration_days',
            ]);
        });
        Schema::dropIfExists('leave_balances');
    }
};

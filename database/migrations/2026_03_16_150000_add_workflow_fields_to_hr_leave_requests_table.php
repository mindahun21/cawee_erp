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
        Schema::table('hr_leave_requests', function (Blueprint $table) {
            // New workflow fields
            $table->string('supervisor_status')->default('Pending')->after('status');
            $table->foreignId('supervisor_approved_by')->nullable()->constrained('employees')->onDelete('set null')->after('supervisor_status');
            $table->timestamp('supervisor_approved_at')->nullable()->after('supervisor_approved_by');

            $table->string('hr_status')->default('Pending')->after('supervisor_approved_at');
            $table->foreignId('hr_approved_by')->nullable()->constrained('employees')->onDelete('set null')->after('hr_status');
            $table->timestamp('hr_approved_at')->nullable()->after('hr_approved_by');

            $table->foreignId('director_approved_by')->nullable()->constrained('employees')->onDelete('set null')->after('hr_approved_at');
            $table->timestamp('director_approved_at')->nullable()->after('director_approved_by');

            $table->string('supporting_document')->nullable()->after('director_approved_at');
            $table->text('remarks')->nullable()->after('supporting_document');
            $table->date('approval_date')->nullable()->after('remarks');
            
            // Re-using 'status' as 'approval_status' if possible, or adding it as overall status
            // The old model used 'approval_status' for the overall status.
            $table->string('approval_status')->default('Pending')->after('approval_date');
            
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_leave_requests', function (Blueprint $table) {
            $table->dropForeign(['supervisor_approved_by']);
            $table->dropForeign(['hr_approved_by']);
            $table->dropForeign(['director_approved_by']);
            $table->dropColumn([
                'supervisor_status',
                'supervisor_approved_by',
                'supervisor_approved_at',
                'hr_status',
                'hr_approved_by',
                'hr_approved_at',
                'director_approved_by',
                'director_approved_at',
                'supporting_document',
                'remarks',
                'approval_date',
                'approval_status',
            ]);
            $table->dropSoftDeletes();
        });
    }
};

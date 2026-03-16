<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration 
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For production safety, we don't just drop the table.
        // We ensure data is preserved if it exists.
        if (Schema::hasTable('hr_leave_requests')) {
            // Rename to backup
            Schema::rename('hr_leave_requests', 'hr_leave_requests_old_v1');
        }

        Schema::create('hr_leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('hr_leave_type_id')->constrained('hr_leave_types')->onDelete('cascade');
            $table->date('start_date');
            $table->date('end_date');
            $table->text('reason')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('supervisor_id')->nullable()->constrained('employees')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        // Copy data back if it existed
        if (Schema::hasTable('hr_leave_requests_old_v1')) {
            // We use DB::table to avoid model dependency issues during migrations
            $oldData = DB::table('hr_leave_requests_old_v1')->get();
            foreach ($oldData as $row) {
                DB::table('hr_leave_requests')->insert((array) $row);
            }
            Schema::dropIfExists('hr_leave_requests_old_v1');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_leave_requests');
    }
};

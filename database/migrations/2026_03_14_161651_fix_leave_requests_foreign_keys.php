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
        $backupTable = 'hr_leave_requests_old_v1';
        $hasBackup = Schema::hasTable($backupTable);
        $shouldCopy = $hasBackup;

        if (Schema::hasTable('hr_leave_requests') && ! $hasBackup) {
            $this->dropForeignKeyIfExists('hr_leave_requests', 'hr_leave_requests_employee_id_foreign');
            $this->dropForeignKeyIfExists('hr_leave_requests', 'hr_leave_requests_hr_leave_type_id_foreign');
            $this->dropForeignKeyIfExists('hr_leave_requests', 'hr_leave_requests_supervisor_id_foreign');

            // Rename to backup
            Schema::rename('hr_leave_requests', $backupTable);
            $shouldCopy = true;
        }

        // Ensure any stale table is removed before creating the fresh schema
        // Ensure the backup table has no conflicting constraints before rebuilding
        $this->ensureBackupConstraintsDropped($backupTable);
        Schema::dropIfExists('hr_leave_requests');

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
        if ($shouldCopy && Schema::hasTable($backupTable)) {
            // We use DB::table to avoid model dependency issues during migrations
            $oldData = DB::table($backupTable)->get();
            foreach ($oldData as $row) {
                DB::table('hr_leave_requests')->insert((array) $row);
            }
            Schema::dropIfExists($backupTable);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_leave_requests');
    }

    private function ensureBackupConstraintsDropped(string $backupTable): void
    {
        if (! Schema::hasTable($backupTable)) {
            return;
        }

        $this->dropForeignKeyIfExists($backupTable, 'hr_leave_requests_employee_id_foreign');
        $this->dropForeignKeyIfExists($backupTable, 'hr_leave_requests_hr_leave_type_id_foreign');
        $this->dropForeignKeyIfExists($backupTable, 'hr_leave_requests_supervisor_id_foreign');
    }

    private function dropForeignKeyIfExists(string $table, string $constraint): void
    {
        $database = DB::connection()->getDatabaseName();
        $exists = DB::table('information_schema.table_constraints')
            ->where('constraint_schema', $database)
            ->where('table_name', $table)
            ->where('constraint_name', $constraint)
            ->exists();

        if ($exists) {
            Schema::table($table, function (Blueprint $tableBlueprint) use ($constraint) {
                $tableBlueprint->dropForeign($constraint);
            });
        }
    }
};

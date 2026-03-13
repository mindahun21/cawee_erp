<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Rename user_id -> employee_id only if user_id still exists
        if (Schema::hasColumn('asset_assignments', 'user_id')) {
            // Check if the FK actually exists before trying to drop it
            $fkExists = \DB::select("
                SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'asset_assignments'
                AND CONSTRAINT_NAME = 'asset_assignments_user_id_foreign'
                AND CONSTRAINT_TYPE = 'FOREIGN KEY'
            ");

            Schema::table('asset_assignments', function (Blueprint $table) use ($fkExists) {
                if (!empty($fkExists)) {
                    $table->dropForeign(['user_id']);
                }
                $table->renameColumn('user_id', 'employee_id');
            });
        }

        // Add FK on employee_id only if it doesn't already exist
        $empFkExists = \DB::select("
            SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'asset_assignments'
            AND CONSTRAINT_NAME = 'asset_assignments_employee_id_foreign'
            AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ");

        if (Schema::hasColumn('asset_assignments', 'employee_id') && empty($empFkExists)) {
            Schema::table('asset_assignments', function (Blueprint $table) {
                $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asset_assignments', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->renameColumn('employee_id', 'user_id');
        });

        Schema::table('asset_assignments', function (Blueprint $table) {
             $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
};

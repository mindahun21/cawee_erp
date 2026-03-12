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
        Schema::table('asset_assignments', function (Blueprint $table) {
            $table->dropForeign(['user_id']); // Assuming it had a constraint
            $table->renameColumn('user_id', 'employee_id');
        });

        Schema::table('asset_assignments', function (Blueprint $table) {
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
        });
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

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
        // Fix warehouses table foreign key
        if (Schema::hasTable('warehouses') && Schema::hasColumn('warehouses', 'manager_id')) {
            Schema::table('warehouses', function (Blueprint $table) {
                try {
                    $table->dropForeign(['manager_id']);
                } catch (\Exception $e) {
                    // Ignore if foreign key doesn't exist or cannot be dropped
                }
                $table->foreign('manager_id')->references('id')->on('employees')->onDelete('set null');
            });
        }

        // Fix inventory_movements table foreign key
        if (Schema::hasTable('inventory_movements') && Schema::hasColumn('inventory_movements', 'approved_by_id')) {
            Schema::table('inventory_movements', function (Blueprint $table) {
                try {
                    $table->dropForeign(['approved_by_id']);
                } catch (\Exception $e) {
                    // Ignore if foreign key doesn't exist or cannot be dropped
                }
                $table->foreign('approved_by_id')->references('id')->on('employees')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverting would point back to the non-existent hr_employees, which we don't want.
    }
};

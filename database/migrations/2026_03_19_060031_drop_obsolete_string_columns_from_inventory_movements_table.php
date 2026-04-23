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
        if (!Schema::hasTable('inventory_movements')) {
            return;
        }

        if (Schema::hasColumn('inventory_movements', 'status')) {
            $hasStatusData = DB::table('inventory_movements')->whereNotNull('status')->limit(1)->exists();
            if (!$hasStatusData) {
                Schema::table('inventory_movements', function (Blueprint $table) {
                    $table->dropColumn('status');
                });
            }
        }

        if (Schema::hasColumn('inventory_movements', 'reason')) {
            $hasReasonData = DB::table('inventory_movements')->whereNotNull('reason')->limit(1)->exists();
            if (!$hasReasonData) {
                Schema::table('inventory_movements', function (Blueprint $table) {
                    $table->dropColumn('reason');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('inventory_movements')) {
            return;
        }

        Schema::table('inventory_movements', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_movements', 'status')) {
                $table->string('status')->nullable();
            }
            if (!Schema::hasColumn('inventory_movements', 'reason')) {
                $table->string('reason')->nullable();
            }
        });
    }
};

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
        if (!Schema::hasTable('inventory_movements') || !Schema::hasColumn('inventory_movements', 'type')) {
            return;
        }

        $hasData = DB::table('inventory_movements')->whereNotNull('type')->limit(1)->exists();
        if ($hasData) {
            return;
        }

        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('inventory_movements') || Schema::hasColumn('inventory_movements', 'type')) {
            return;
        }

        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->string('type')->nullable();
        });
    }
};

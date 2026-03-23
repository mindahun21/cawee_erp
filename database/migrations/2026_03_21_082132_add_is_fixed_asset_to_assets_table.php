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
        if (!Schema::hasTable('assets') || Schema::hasColumn('assets', 'is_fixed_asset')) {
            return;
        }

        Schema::table('assets', function (Blueprint $table) {
            $table->boolean('is_fixed_asset')->default(true)->after('unit_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('assets') || !Schema::hasColumn('assets', 'is_fixed_asset')) {
            return;
        }

        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn('is_fixed_asset');
        });
    }
};

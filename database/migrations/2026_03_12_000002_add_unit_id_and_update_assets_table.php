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
        Schema::table('assets', function (Blueprint $table) {
            if (!Schema::hasColumn('assets', 'unit_id')) {
                $table->foreignId('unit_id')->nullable()->constrained('units')->onDelete('set null');
            }
            if (Schema::hasColumn('assets', 'is_fixed_asset')) {
                $table->dropColumn('is_fixed_asset');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->boolean('is_fixed_asset')->default(true);
            $table->dropForeign(['unit_id']);
            $table->dropColumn('unit_id');
        });
    }
};

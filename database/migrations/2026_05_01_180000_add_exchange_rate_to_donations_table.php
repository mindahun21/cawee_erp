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
        if (!Schema::hasColumn('donations', 'exchange_rate')) {
            Schema::table('donations', function (Blueprint $table) {
                $table->decimal('exchange_rate', 15, 6)->default(1.000000)->after('currency_id');
            });
        }

        if (!Schema::hasColumn('donations', 'base_amount')) {
            Schema::table('donations', function (Blueprint $table) {
                $table->decimal('base_amount', 15, 2)->nullable()->after('exchange_rate');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('donations', 'base_amount')) {
            Schema::table('donations', function (Blueprint $table) {
                $table->dropColumn('base_amount');
            });
        }

        if (Schema::hasColumn('donations', 'exchange_rate')) {
            Schema::table('donations', function (Blueprint $table) {
                $table->dropColumn('exchange_rate');
            });
        }
    }
};

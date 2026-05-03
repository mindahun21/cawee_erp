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
        if (!Schema::hasColumn('campaigns', 'exchange_rate')) {
            Schema::table('campaigns', function (Blueprint $table) {
                $table->decimal('exchange_rate', 15, 6)->default(1.000000)->after('budget');
            });
        }

        if (!Schema::hasColumn('campaigns', 'base_goal_amount')) {
            Schema::table('campaigns', function (Blueprint $table) {
                $table->decimal('base_goal_amount', 15, 2)->nullable()->after('exchange_rate');
            });
        }

        if (!Schema::hasColumn('campaigns', 'base_budget')) {
            Schema::table('campaigns', function (Blueprint $table) {
                $table->decimal('base_budget', 15, 2)->nullable()->after('base_goal_amount');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('campaigns', 'base_budget')) {
            Schema::table('campaigns', function (Blueprint $table) {
                $table->dropColumn('base_budget');
            });
        }

        if (Schema::hasColumn('campaigns', 'base_goal_amount')) {
            Schema::table('campaigns', function (Blueprint $table) {
                $table->dropColumn('base_goal_amount');
            });
        }

        if (Schema::hasColumn('campaigns', 'exchange_rate')) {
            Schema::table('campaigns', function (Blueprint $table) {
                $table->dropColumn('exchange_rate');
            });
        }
    }
};

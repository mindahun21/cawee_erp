<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Planning Module — Patch: ensure status column exists on plans table.
     *
     * This migration is idempotent. On a fresh install where
     * create_planning_tables already runs with the correct schema,
     * it will silently skip. On older installs that ran the original
     * broken migration, it adds the missing status column.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('plans', 'status')) {
            Schema::table('plans', function (Blueprint $table) {
                $table->string('status')->default('draft')->after('progress_percentage');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('plans', 'status')) {
            Schema::table('plans', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};

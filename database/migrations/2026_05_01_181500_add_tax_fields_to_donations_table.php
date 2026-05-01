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
        if (!Schema::hasColumn('donations', 'is_tax_deductible')) {
            Schema::table('donations', function (Blueprint $table) {
                $table->boolean('is_tax_deductible')->default(false)->after('base_amount');
            });
        }

        if (!Schema::hasColumn('donations', 'is_gift_aid_eligible')) {
            Schema::table('donations', function (Blueprint $table) {
                $table->boolean('is_gift_aid_eligible')->default(false)->after('is_tax_deductible');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('donations', 'is_gift_aid_eligible')) {
            Schema::table('donations', function (Blueprint $table) {
                $table->dropColumn('is_gift_aid_eligible');
            });
        }

        if (Schema::hasColumn('donations', 'is_tax_deductible')) {
            Schema::table('donations', function (Blueprint $table) {
                $table->dropColumn('is_tax_deductible');
            });
        }
    }
};

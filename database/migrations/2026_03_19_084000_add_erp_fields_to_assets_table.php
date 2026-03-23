<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            if (!Schema::hasColumn('assets', 'asset_tag')) {
                $table->string('asset_tag')->nullable()->unique()->after('name');
            }
            if (!Schema::hasColumn('assets', 'image')) {
                $table->string('image')->nullable()->after('description');
            }
            if (!Schema::hasColumn('assets', 'insurance_policy_no')) {
                $table->string('insurance_policy_no')->nullable()->after('warranty_expiry_date');
            }
            if (!Schema::hasColumn('assets', 'insurance_provider')) {
                $table->string('insurance_provider')->nullable()->after('insurance_policy_no');
            }
            if (!Schema::hasColumn('assets', 'insurance_expiry_date')) {
                $table->date('insurance_expiry_date')->nullable()->after('insurance_provider');
            }
            if (!Schema::hasColumn('assets', 'end_of_life_date')) {
                $table->date('end_of_life_date')->nullable()->after('insurance_expiry_date');
            }
            if (!Schema::hasColumn('assets', 'disposal_method')) {
                $table->string('disposal_method')->nullable()->after('end_of_life_date');
            }
            if (!Schema::hasColumn('assets', 'disposal_date')) {
                $table->date('disposal_date')->nullable()->after('disposal_method');
            }
            if (!Schema::hasColumn('assets', 'disposal_value')) {
                $table->decimal('disposal_value', 15, 2)->nullable()->after('disposal_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn([
                'asset_tag', 'image',
                'insurance_policy_no', 'insurance_provider', 'insurance_expiry_date',
                'end_of_life_date', 'disposal_method', 'disposal_date', 'disposal_value',
            ]);
        });
    }
};

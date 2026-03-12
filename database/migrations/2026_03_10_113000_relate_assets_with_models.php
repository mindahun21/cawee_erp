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
            // Drop the old string model field and redundant FKs
            if (Schema::hasColumn('assets', 'model')) {
                $table->dropColumn('model');
            }
            if (Schema::hasColumn('assets', 'asset_category_id')) {
                $table->dropForeign(['asset_category_id']);
                $table->dropColumn('asset_category_id');
            }
            if (Schema::hasColumn('assets', 'depreciation_id')) {
                $table->dropForeign(['depreciation_id']);
                $table->dropColumn('depreciation_id');
            }

            // Add the new relationship field
            $table->foreignId('asset_model_id')
                ->nullable()
                ->after('name')
                ->constrained('asset_models')
                ->nullOnDelete();

            // Add the notes field
            $table->text('notes')->nullable()->before('contract_details');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropForeign(['asset_model_id']);
            $table->dropColumn('asset_model_id');
            $table->dropColumn('notes');
            $table->string('model')->nullable()->after('name');
        });
    }
};

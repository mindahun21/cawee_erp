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
            $table->foreignId('asset_condition_id')->nullable()->constrained('asset_conditions')->nullOnDelete();
            $table->foreignId('asset_status_id')->nullable()->constrained('asset_statuses')->nullOnDelete();
            $table->foreignId('acquisition_type_id')->nullable()->constrained('acquisition_types')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropForeign(['asset_condition_id']);
            $table->dropForeign(['asset_status_id']);
            $table->dropForeign(['acquisition_type_id']);
            $table->dropColumn(['asset_condition_id', 'asset_status_id', 'acquisition_type_id']);
        });
    }
};

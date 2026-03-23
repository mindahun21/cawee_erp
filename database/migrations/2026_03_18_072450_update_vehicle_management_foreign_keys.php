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
        if (!Schema::hasTable('vehicles')) {
            return;
        }

        Schema::table('vehicles', function (Blueprint $table) {
            try {
                $table->dropForeign(['vehicle_type_id']);
            } catch (\Exception $e) {
                // Ignore if FK doesn't exist
            }
            try {
                $table->dropForeign(['vehicle_status_id']);
            } catch (\Exception $e) {
                // Ignore if FK doesn't exist
            }

            if (Schema::hasColumn('vehicles', 'vehicle_type_id')) {
                $table->foreign('vehicle_type_id')->references('id')->on('hr_setting_options');
            }
            if (Schema::hasColumn('vehicles', 'vehicle_status_id')) {
                $table->foreign('vehicle_status_id')->references('id')->on('hr_setting_options')->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('vehicles')) {
            return;
        }

        Schema::table('vehicles', function (Blueprint $table) {
            try {
                $table->dropForeign(['vehicle_type_id']);
            } catch (\Exception $e) {
                // Ignore if FK doesn't exist
            }
            try {
                $table->dropForeign(['vehicle_status_id']);
            } catch (\Exception $e) {
                // Ignore if FK doesn't exist
            }

            if (Schema::hasColumn('vehicles', 'vehicle_type_id')) {
                $table->foreign('vehicle_type_id')->references('id')->on('vehicle_types');
            }
            if (Schema::hasColumn('vehicles', 'vehicle_status_id')) {
                $table->foreign('vehicle_status_id')->references('id')->on('vehicle_statuses')->onDelete('cascade');
            }
        });
    }
};

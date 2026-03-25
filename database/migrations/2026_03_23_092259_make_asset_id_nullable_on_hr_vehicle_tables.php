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
        $tables = [
            'hr_vehicle_service_requests',
            'hr_vehicle_licenses',
            'hr_vehicle_maintenance_records',
            'hr_vehicle_inspections',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'asset_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->unsignedBigInteger('asset_id')->nullable()->change();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No down migration because changing to NOT NULL would fail if there are null values.
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'hr_vehicle_service_requests',
            'hr_vehicle_licenses',
            'hr_vehicle_maintenance_records',
            'hr_vehicle_inspections',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'vehicle_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->foreignId('vehicle_id')
                        ->nullable()
                        ->after('id')
                        ->constrained('vehicles')
                        ->nullOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        $tables = [
            'hr_vehicle_service_requests',
            'hr_vehicle_licenses',
            'hr_vehicle_maintenance_records',
            'hr_vehicle_inspections',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'vehicle_id')) {
                Schema::table($table, function (Blueprint $t) use ($table) {
                    $t->dropForeignIdFor(\App\Models\Vehicle::class);
                    $t->dropColumn('vehicle_id');
                });
            }
        }
    }
};

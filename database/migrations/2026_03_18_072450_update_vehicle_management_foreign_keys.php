<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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

        $schema = DB::getDatabaseName();
        $fks = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('TABLE_SCHEMA', $schema)
            ->where('TABLE_NAME', 'vehicles')
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->pluck('CONSTRAINT_NAME')
            ->all();

        if (in_array('vehicles_vehicle_type_id_foreign', $fks, true)) {
            DB::statement('ALTER TABLE `vehicles` DROP FOREIGN KEY `vehicles_vehicle_type_id_foreign`');
        }
        if (in_array('vehicles_vehicle_status_id_foreign', $fks, true)) {
            DB::statement('ALTER TABLE `vehicles` DROP FOREIGN KEY `vehicles_vehicle_status_id_foreign`');
        }

        Schema::table('vehicles', function (Blueprint $table) {
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

        $schema = DB::getDatabaseName();
        $fks = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('TABLE_SCHEMA', $schema)
            ->where('TABLE_NAME', 'vehicles')
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->pluck('CONSTRAINT_NAME')
            ->all();

        if (in_array('vehicles_vehicle_type_id_foreign', $fks, true)) {
            DB::statement('ALTER TABLE `vehicles` DROP FOREIGN KEY `vehicles_vehicle_type_id_foreign`');
        }
        if (in_array('vehicles_vehicle_status_id_foreign', $fks, true)) {
            DB::statement('ALTER TABLE `vehicles` DROP FOREIGN KEY `vehicles_vehicle_status_id_foreign`');
        }

        Schema::table('vehicles', function (Blueprint $table) {
            if (Schema::hasColumn('vehicles', 'vehicle_type_id')) {
                $table->foreign('vehicle_type_id')->references('id')->on('vehicle_types');
            }
            if (Schema::hasColumn('vehicles', 'vehicle_status_id')) {
                $table->foreign('vehicle_status_id')->references('id')->on('vehicle_statuses')->onDelete('cascade');
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * This migration consolidates final MySQL compatibility and performance fixes
     * for the Vehicle-Asset integration.
     */
    public function up(): void
    {
        // 1. Fix Assets table nullability and indices
        if (Schema::hasTable('assets')) {
            Schema::table('assets', function (Blueprint $table) {
                // Ensure purchase_cost is nullable (MySQL needs this for strict mode)
                if (Schema::hasColumn('assets', 'purchase_cost')) {
                    $table->decimal('purchase_cost', 15, 2)->nullable()->change();
                }
            });

            // Modern Laravel 12 way to check indices
            $indexName = 'assets_asset_tag_unique';
            $indexes = Schema::getIndexes('assets');
            $hasIndex = collect($indexes)->contains('name', $indexName);

            $hasDuplicates = false;
            if (Schema::hasColumn('assets', 'asset_tag')) {
                $hasDuplicates = DB::table('assets')
                    ->select('asset_tag')
                    ->whereNotNull('asset_tag')
                    ->groupBy('asset_tag')
                    ->havingRaw('COUNT(*) > 1')
                    ->limit(1)
                    ->exists();
            }

            if (!$hasIndex && !$hasDuplicates && Schema::hasColumn('assets', 'asset_tag')) {
                 Schema::table('assets', function (Blueprint $table) use ($indexName) {
                     $table->unique('asset_tag', $indexName);
                 });
            }
        }

        // 2. Add indices to HR Vehicle tables for the new vehicle_id column
        $hrTables = [
            'hr_vehicle_service_requests',
            'hr_vehicle_licenses',
            'hr_vehicle_maintenance_records',
            'hr_vehicle_inspections',
        ];

        foreach ($hrTables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'vehicle_id')) {
                // Check if index exists first
                $indexes = Schema::getIndexes($table);
                $hasIndex = collect($indexes)->contains(fn ($idx) => in_array('vehicle_id', $idx['columns'] ?? []));
                
                if (!$hasIndex) {
                    Schema::table($table, function (Blueprint $t) {
                        $t->index('vehicle_id');
                    });
                }
            }
        }

        // 3. Ensure vehicles.plate_number is indexed
        if (Schema::hasTable('vehicles') && Schema::hasColumn('vehicles', 'plate_number')) {
             $indexName = 'vehicles_plate_number_index';
             $indexes = Schema::getIndexes('vehicles');
             $hasIndex = collect($indexes)->contains('name', $indexName);

             if (!$hasIndex) {
                Schema::table('vehicles', function (Blueprint $table) use ($indexName) {
                    $table->index('plate_number', $indexName);
                });
             }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Down migration can be empty as these are mostly additive/cleanup
    }
};

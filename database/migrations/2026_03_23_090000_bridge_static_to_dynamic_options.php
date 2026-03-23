<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add foreign key columns
        if (Schema::hasTable('warehouses')) {
            Schema::table('warehouses', function (Blueprint $table) {
                if (!Schema::hasColumn('warehouses', 'warehouse_type_id') && Schema::hasTable('warehouse_types')) {
                    $table->foreignId('warehouse_type_id')->nullable()->constrained('warehouse_types');
                }
                if (!Schema::hasColumn('warehouses', 'country_id') && Schema::hasTable('countries')) {
                    $table->foreignId('country_id')->nullable()->constrained('countries');
                }
            });
        }

        if (Schema::hasTable('assets')) {
            Schema::table('assets', function (Blueprint $table) {
                if (!Schema::hasColumn('assets', 'disposal_method_id') && Schema::hasTable('asset_disposal_methods')) {
                    $table->foreignId('disposal_method_id')->nullable()->constrained('asset_disposal_methods');
                }
            });
        }

        // Migrate existing warehouse_type data
        if (Schema::hasTable('warehouses') && Schema::hasTable('warehouse_types') && Schema::hasColumn('warehouses', 'warehouse_type')) {
            $warehouseTypes = DB::table('warehouses')->distinct()->pluck('warehouse_type')->filter();
            foreach ($warehouseTypes as $type) {
                $typeStr = (string) $type;
                $label = match($typeStr) {
                    'main' => 'Main Store',
                    'satellite' => 'Satellite / Sub-Store',
                    'cold_storage' => 'Cold Storage',
                    'transit' => 'Transit / Staging',
                    default => ucfirst($typeStr),
                };
                $id = DB::table('warehouse_types')->where('name', $label)->value('id');
                if (!$id) {
                    $id = DB::table('warehouse_types')->insertGetId([
                        'name' => $label,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                DB::table('warehouses')->where('warehouse_type', $type)->update(['warehouse_type_id' => $id]);
            }
        }

        // Migrate existing country data
        if (Schema::hasTable('warehouses') && Schema::hasTable('countries') && Schema::hasColumn('warehouses', 'country')) {
            $countries = DB::table('warehouses')->distinct()->pluck('country')->filter();
            foreach ($countries as $countryName) {
                DB::table('countries')->updateOrInsert(
                    ['name' => $countryName],
                    ['created_at' => now(), 'updated_at' => now()]
                );
                $countryRecord = DB::table('countries')->where('name', $countryName)->first();
                if ($countryRecord) {
                    DB::table('warehouses')->where('country', $countryName)->update(['country_id' => $countryRecord->id]);
                }
            }
        }

        // Migrate existing asset disposal_method data
        if (Schema::hasTable('assets') && Schema::hasTable('asset_disposal_methods') && Schema::hasColumn('assets', 'disposal_method')) {
            $disposalMethods = DB::table('assets')->distinct()->pluck('disposal_method')->filter();
            foreach ($disposalMethods as $method) {
                $methodStr = (string) $method;
                $label = match($methodStr) {
                    'sold' => 'Sold',
                    'scrapped' => 'Scrapped',
                    'donated' => 'Donated',
                    'written_off' => 'Written Off',
                    default => ucfirst($methodStr),
                };
                $id = DB::table('asset_disposal_methods')->where('name', $label)->value('id');
                if (!$id) {
                    $id = DB::table('asset_disposal_methods')->insertGetId([
                        'name' => $label,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                DB::table('assets')->where('disposal_method', $method)->update(['disposal_method_id' => $id]);
            }
        }

        // keep the old columns for now to avoid data loss, 
        // but we'll drop them in a separate cleanup migration if needed.
        // Or we can drop them now if we are confident.
        // Since it's a dev environment, let's drop them.
        /*
        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropColumn(['warehouse_type', 'country']);
        });
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn('disposal_method');
        });
        */
    }

    public function down(): void
    {
        if (Schema::hasTable('warehouses')) {
            Schema::table('warehouses', function (Blueprint $table) {
                try {
                    $table->dropForeign(['warehouse_type_id']);
                } catch (\Exception $e) {
                    // Ignore if FK doesn't exist
                }
                if (Schema::hasColumn('warehouses', 'warehouse_type_id')) {
                    $table->dropColumn('warehouse_type_id');
                }
                try {
                    $table->dropForeign(['country_id']);
                } catch (\Exception $e) {
                    // Ignore if FK doesn't exist
                }
                if (Schema::hasColumn('warehouses', 'country_id')) {
                    $table->dropColumn('country_id');
                }
            });
        }

        if (Schema::hasTable('assets')) {
            Schema::table('assets', function (Blueprint $table) {
                try {
                    $table->dropForeign(['disposal_method_id']);
                } catch (\Exception $e) {
                    // Ignore if FK doesn't exist
                }
                if (Schema::hasColumn('assets', 'disposal_method_id')) {
                    $table->dropColumn('disposal_method_id');
                }
            });
        }
    }
};

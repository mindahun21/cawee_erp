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
        // 1. Modify hr_locations type column to string
        Schema::table('hr_locations', function (Blueprint $table) {
            $table->string('type')->nullable()->change();
        });

        // 2. Migrate data from locations to hr_locations
        $locations = DB::table('locations')->get();
        foreach ($locations as $location) {
            DB::table('hr_locations')->insert([
                'id' => $location->id, // Preserve IDs for FK consistency
                'location_name' => $location->name,
                'type' => $location->type,
                'address' => $location->description, // description -> address as fallback
                'created_at' => $location->created_at,
                'updated_at' => $location->updated_at,
            ]);
        }

        // 3. Migrate data from departments to hr_departments
        $departments = DB::table('departments')->get();
        foreach ($departments as $dept) {
            // Check if ID already exists to avoid collisions
            if (!DB::table('hr_departments')->where('id', $dept->id)->exists()) {
                DB::table('hr_departments')->insert([
                    'id' => $dept->id,
                    'name' => $dept->name,
                    'created_at' => $dept->created_at,
                    'updated_at' => $dept->updated_at,
                ]);
            } else {
                // If ID exists, just update name or skip if identical
                DB::table('hr_departments')->where('id', $dept->id)->update([
                    'name' => $dept->name,
                ]);
            }
        }

        // 4. Update Assets foreign keys
        Schema::table('assets', function (Blueprint $table) {
            $table->dropForeign(['location_id']);
            $table->dropForeign(['department_id']);
            
            $table->foreign('location_id')->references('id')->on('hr_locations')->onDelete('set null');
            $table->foreign('department_id')->references('id')->on('hr_departments')->onDelete('set null');
        });

        // 5. Update Inventory Movements foreign keys
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropForeign(['from_location_id']);
            $table->dropForeign(['to_location_id']);
            
            $table->foreign('from_location_id')->references('id')->on('hr_locations')->onDelete('set null');
            $table->foreign('to_location_id')->references('id')->on('hr_locations')->onDelete('set null');
        });

        // 6. Update Asset Stocks foreign keys
        Schema::table('asset_stocks', function (Blueprint $table) {
            $table->dropForeign(['location_id']);
            $table->dropForeign(['department_id']);
            
            $table->foreign('location_id')->references('id')->on('hr_locations')->onDelete('cascade');
            $table->foreign('department_id')->references('id')->on('hr_departments')->onDelete('cascade');
        });

        // 7. Update Asset Assignments foreign keys
        Schema::table('asset_assignments', function (Blueprint $table) {
            $table->dropForeign(['location_id']);
            $table->dropForeign(['department_id']);
            
            $table->foreign('location_id')->references('id')->on('hr_locations')->onDelete('set null');
            $table->foreign('department_id')->references('id')->on('hr_departments')->onDelete('set null');
        });

        // 8. Drop redundant tables
        Schema::dropIfExists('departments');
        Schema::dropIfExists('locations');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-create redundant tables
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // This is a destructive migration, full rollback of data is complex
        // We skip data rollback for simplicity in this context unless strictly required
    }
};

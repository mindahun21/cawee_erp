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
        Schema::table('inventory_movements', function (Blueprint $table) {
            // Drop old columns only if they exist
            if (Schema::hasColumn('inventory_movements', 'type')) {
                $table->dropColumn('type');
            }
            if (Schema::hasColumn('inventory_movements', 'asset_id')) {
                $table->dropForeign(['asset_id']);
                $table->dropColumn('asset_id');
            }
            if (Schema::hasColumn('inventory_movements', 'from_location_id')) {
                $table->dropForeign(['from_location_id']);
                $table->dropColumn('from_location_id');
            }

            // Add new columns only if they don't exist
            if (!Schema::hasColumn('inventory_movements', 'item_id')) {
                $table->foreignId('item_id')->after('id')->constrained()->onDelete('cascade');
            }
            if (!Schema::hasColumn('inventory_movements', 'from_warehouse_id')) {
                $table->foreignId('from_warehouse_id')->after('item_id')->constrained('warehouses')->onDelete('cascade');
            }
            if (!Schema::hasColumn('inventory_movements', 'destination_type')) {
                $table->string('destination_type')->nullable()->after('from_warehouse_id');
            }
            if (!Schema::hasColumn('inventory_movements', 'to_warehouse_id')) {
                $table->foreignId('to_warehouse_id')->nullable()->after('destination_type')->constrained('warehouses')->onDelete('set null');
            }
            if (!Schema::hasColumn('inventory_movements', 'to_location_id')) {
                // Use unsignedBigInteger to avoid FK to non-existent 'locations' table
                $table->unsignedBigInteger('to_location_id')->nullable()->after('to_warehouse_id');
            }
            if (!Schema::hasColumn('inventory_movements', 'to_department_id')) {
                // Use unsignedBigInteger to avoid FK to non-existent 'departments' table
                $table->unsignedBigInteger('to_department_id')->nullable()->after('to_location_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->string('type')->nullable();
            $table->foreignId('asset_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('from_location_id')->nullable()->constrained('locations')->onDelete('set null');
            $table->foreignId('to_location_id')->nullable()->constrained('locations')->onDelete('set null');

            $table->dropColumn(['item_id', 'from_warehouse_id', 'destination_type', 'to_warehouse_id', 'to_location_id', 'to_department_id']);
        });
    }
};

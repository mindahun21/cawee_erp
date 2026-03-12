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
            $table->dropColumn('type');
            $table->dropForeign(['asset_id']);
            $table->dropColumn('asset_id');
            $table->dropForeign(['from_location_id']);
            $table->dropColumn('from_location_id');
            $table->dropForeign(['to_location_id']);
            $table->dropColumn('to_location_id');

            $table->foreignId('item_id')->after('id')->constrained()->onDelete('cascade');
            $table->foreignId('from_warehouse_id')->after('item_id')->constrained('warehouses')->onDelete('cascade');
            $table->string('destination_type')->nullable()->after('from_warehouse_id'); // 'warehouse' or 'location_department'
            $table->foreignId('to_warehouse_id')->nullable()->after('destination_type')->constrained('warehouses')->onDelete('set null');
            $table->foreignId('to_location_id')->nullable()->after('to_warehouse_id')->constrained('locations')->onDelete('set null');
            $table->foreignId('to_department_id')->nullable()->after('to_location_id')->constrained('departments')->onDelete('set null');
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

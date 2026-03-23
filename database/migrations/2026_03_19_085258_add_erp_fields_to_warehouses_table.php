<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            if (!Schema::hasColumn('warehouses', 'manager_id')) {
                $table->foreignId('manager_id')->nullable()->constrained('employees')->onDelete('set null')->after('name');
            }
            if (!Schema::hasColumn('warehouses', 'warehouse_type')) {
                $table->string('warehouse_type')->nullable()->after('manager_id');
            }
            if (!Schema::hasColumn('warehouses', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('warehouse_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropForeign(['manager_id']);
            $table->dropColumn(['manager_id', 'warehouse_type', 'is_active']);
        });
    }
};

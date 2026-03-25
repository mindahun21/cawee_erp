<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_warehouse', function (Blueprint $table) {
            if (!Schema::hasColumn('item_warehouse', 'bin_location')) {
                $table->string('bin_location')->nullable()->after('warehouse_id');
            }
            if (!Schema::hasColumn('item_warehouse', 'expiry_date')) {
                $table->date('expiry_date')->nullable()->after('purchase_date');
            }
            if (!Schema::hasColumn('item_warehouse', 'batch_number')) {
                $table->string('batch_number')->nullable()->after('sku');
            }
        });
    }

    public function down(): void
    {
        Schema::table('item_warehouse', function (Blueprint $table) {
            $table->dropColumn(['bin_location', 'expiry_date', 'batch_number']);
        });
    }
};

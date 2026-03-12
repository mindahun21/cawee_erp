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
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('sku');
            $table->dropColumn('acquisition_type_id');
            $table->dropColumn('currency_id');
            $table->dropColumn('purchase_cost');
            $table->dropColumn('purchase_date');
            $table->dropColumn('warranty_expiry');
            $table->dropColumn('supplier_id');
            $table->dropColumn('donor_id');
        });

        Schema::table('item_warehouse', function (Blueprint $table) {
            $table->string('sku')->after('warehouse_id')->nullable();
            $table->foreignId('acquisition_type_id')->nullable()->constrained('acquisition_types')->onDelete('set null');
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->onDelete('set null');
            $table->decimal('purchase_cost', 15, 2)->nullable();
            $table->date('purchase_date')->nullable();
            $table->date('warranty_expiry')->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained('procurement_suppliers')->onDelete('set null');
            $table->foreignId('donor_id')->nullable()->constrained('donors')->onDelete('set null');
            $table->integer('quantity')->default(0);
            $table->integer('min_stock_value')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('item_warehouse', function (Blueprint $table) {
            $table->dropColumn('sku');
            $table->dropColumn('acquisition_type_id');
            $table->dropColumn('currency_id');
            $table->dropColumn('purchase_cost');
            $table->dropColumn('purchase_date');
            $table->dropColumn('warranty_expiry');
            $table->dropColumn('supplier_id');
            $table->dropColumn('donor_id');
            $table->dropColumn('quantity');
            $table->dropColumn('min_stock_value');
        });

        Schema::table('items', function (Blueprint $table) {
            $table->string('sku')->nullable()->after('name');
            $table->foreignId('acquisition_type_id')->nullable()->constrained('acquisition_types')->onDelete('set null');
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->onDelete('set null');
            $table->decimal('purchase_cost', 15, 2)->nullable();
            $table->date('purchase_date')->nullable();
            $table->date('warranty_expiry')->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained('procurement_suppliers')->onDelete('set null');
            $table->foreignId('donor_id')->nullable()->constrained('donors')->onDelete('set null');
        });
    }
};

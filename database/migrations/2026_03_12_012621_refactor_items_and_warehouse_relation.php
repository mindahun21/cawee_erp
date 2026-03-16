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
        // Drop columns from items only if they still exist (may already be removed in imported DB)
        Schema::table('items', function (Blueprint $table) {
            $columns = ['sku', 'acquisition_type_id', 'currency_id', 'purchase_cost', 'purchase_date', 'warranty_expiry', 'supplier_id', 'donor_id'];
            $existing = array_filter($columns, fn($col) => Schema::hasColumn('items', $col));
            if (!empty($existing)) {
                $table->dropColumn($existing);
            }
        });

        // Add columns to item_warehouse only if they don't already exist (may already be there in imported DB)
        Schema::table('item_warehouse', function (Blueprint $table) {
            if (!Schema::hasColumn('item_warehouse', 'sku')) {
                $table->string('sku')->after('warehouse_id')->nullable();
            }
            if (!Schema::hasColumn('item_warehouse', 'acquisition_type_id')) {
                $table->foreignId('acquisition_type_id')->nullable()->constrained('acquisition_types')->onDelete('set null');
            }
            if (!Schema::hasColumn('item_warehouse', 'currency_id')) {
                $table->foreignId('currency_id')->nullable()->constrained('currencies')->onDelete('set null');
            }
            if (!Schema::hasColumn('item_warehouse', 'purchase_cost')) {
                $table->decimal('purchase_cost', 15, 2)->nullable();
            }
            if (!Schema::hasColumn('item_warehouse', 'purchase_date')) {
                $table->date('purchase_date')->nullable();
            }
            if (!Schema::hasColumn('item_warehouse', 'warranty_expiry')) {
                $table->date('warranty_expiry')->nullable();
            }
            if (!Schema::hasColumn('item_warehouse', 'supplier_id')) {
                $table->foreignId('supplier_id')->nullable()->constrained('procurement_suppliers')->onDelete('set null');
            }
            if (!Schema::hasColumn('item_warehouse', 'donor_id')) {
                $table->foreignId('donor_id')->nullable()->constrained('donors')->onDelete('set null');
            }
            if (!Schema::hasColumn('item_warehouse', 'quantity')) {
                $table->integer('quantity')->default(0);
            }
            if (!Schema::hasColumn('item_warehouse', 'min_stock_value')) {
                $table->integer('min_stock_value')->default(0);
            }
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

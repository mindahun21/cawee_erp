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
        Schema::create('item_warehouse', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->onDelete('cascade');
            $table->foreignId('warehouse_id')->constrained()->onDelete('cascade');
            
            $table->string('sku')->nullable();
            $table->foreignId('acquisition_type_id')->nullable()->constrained('acquisition_types')->onDelete('set null');
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->onDelete('set null');
            $table->decimal('purchase_cost', 15, 2)->nullable();
            $table->date('purchase_date')->nullable();
            $table->date('warranty_expiry')->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained('procurement_suppliers')->onDelete('set null');
            $table->foreignId('donor_id')->nullable()->constrained('donors')->onDelete('set null');
            $table->integer('quantity')->default(0);
            $table->integer('min_stock_value')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_warehouse');
    }
};

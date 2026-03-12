<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procurement_supplier_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number')->unique();
            $table->foreignId('goods_receipt_id')->constrained('procurement_goods_receipts')->cascadeOnDelete();
            $table->foreignId('purchase_order_id')->constrained('procurement_purchase_orders')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('procurement_suppliers')->cascadeOnDelete();
            $table->date('return_date');
            $table->enum('reason', [
                'Quality Defect',
                'Wrong Item Delivered',
                'Quantity Shortage',
                'Damaged on Arrival',
                'Expired / Past Shelf Life',
                'Other',
            ])->default('Quality Defect');
            $table->text('return_notes')->nullable();
            $table->enum('status', ['Draft', 'Sent to Supplier', 'Acknowledged', 'Completed', 'Cancelled'])
                  ->default('Draft');
            $table->date('expected_resolution_date')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('procurement_supplier_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_return_id')
                  ->constrained('procurement_supplier_returns')
                  ->cascadeOnDelete();
            $table->foreignId('grn_item_id')
                  ->nullable()
                  ->constrained('procurement_goods_receipt_items')
                  ->nullOnDelete();
            $table->string('description');
            $table->decimal('quantity_returned', 12, 4)->default(0);
            $table->string('unit')->nullable();
            $table->enum('reason', [
                'Quality Defect',
                'Wrong Item',
                'Damaged',
                'Expired',
                'Excess Quantity',
                'Other',
            ])->default('Quality Defect');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procurement_supplier_return_items');
        Schema::dropIfExists('procurement_supplier_returns');
    }
};

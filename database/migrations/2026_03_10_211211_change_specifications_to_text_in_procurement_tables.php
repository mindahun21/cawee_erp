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
        Schema::table('procurement_requisition_items', function (Blueprint $table) {
            $table->text('specifications')->nullable()->change();
        });

        Schema::table('procurement_purchase_order_items', function (Blueprint $table) {
            $table->text('specifications')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('procurement_requisition_items', function (Blueprint $table) {
            $table->string('specifications', 500)->nullable()->change();
        });

        Schema::table('procurement_purchase_order_items', function (Blueprint $table) {
            $table->string('specifications', 500)->nullable()->change();
        });
    }
};

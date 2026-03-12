<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add per-line tax, discount, and computed line_total to PO items
        Schema::table('procurement_purchase_order_items', function (Blueprint $table) {
            $table->decimal('tax_rate', 5, 2)->default(0)->after('unit_price');           // e.g. 15 = 15% VAT
            $table->decimal('tax_amount', 15, 2)->default(0)->after('tax_rate');          // computed
            $table->decimal('discount_percent', 5, 2)->default(0)->after('tax_amount');   // e.g. 5 = 5% discount
            $table->decimal('discount_amount', 15, 2)->default(0)->after('discount_percent'); // computed
            $table->decimal('line_total', 15, 2)->default(0)->after('discount_amount');   // qty * unit_price - discount + tax
        });

        // Add discount and other charges to the PO header
        Schema::table('procurement_purchase_orders', function (Blueprint $table) {
            $table->decimal('discount_amount', 15, 2)->default(0)->after('subtotal');          // header-level discount
            $table->decimal('other_charges', 15, 2)->default(0)->after('discount_amount');     // shipping, clearance, etc.
            $table->string('other_charges_description', 200)->nullable()->after('other_charges');
        });
    }

    public function down(): void
    {
        Schema::table('procurement_purchase_order_items', function (Blueprint $table) {
            $table->dropColumn(['tax_rate', 'tax_amount', 'discount_percent', 'discount_amount', 'line_total']);
        });

        Schema::table('procurement_purchase_orders', function (Blueprint $table) {
            $table->dropColumn(['discount_amount', 'other_charges', 'other_charges_description']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('procurement_goods_receipt_items', function (Blueprint $table) {
            // How this line item should be registered post-GRN acceptance
            $table->string('item_type', 20)->default('consumable')->after('inspection_remarks')
                ->comment('consumable = stock/inventory, fixed_asset = asset register, skip = no registration');

            // Timestamp when registration into Inventory/Asset was completed
            $table->timestamp('registered_at')->nullable()->after('item_type');

            // Optional reference back to created asset or inventory movement
            $table->string('registration_ref', 100)->nullable()->after('registered_at');
        });
    }

    public function down(): void
    {
        Schema::table('procurement_goods_receipt_items', function (Blueprint $table) {
            $table->dropColumn(['item_type', 'registered_at', 'registration_ref']);
        });
    }
};

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
        Schema::table('procurement_goods_receipt_items', function (Blueprint $table) {
            // Drop columns added by JSI demo if they exist
            if (Schema::hasColumn('procurement_goods_receipt_items', 'item_type')) {
                $table->dropColumn('item_type');
            }
            if (Schema::hasColumn('procurement_goods_receipt_items', 'registered_at')) {
                $table->dropColumn('registered_at');
            }
            if (Schema::hasColumn('procurement_goods_receipt_items', 'registration_ref')) {
                $table->dropColumn('registration_ref');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('procurement_goods_receipt_items', function (Blueprint $table) {
            $table->string('item_type', 20)->default('consumable')->after('inspection_remarks')
                ->comment('consumable = stock/inventory, fixed_asset = asset register, skip = no registration');
            $table->timestamp('registered_at')->nullable()->after('item_type');
            $table->string('registration_ref', 100)->nullable()->after('registered_at');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            if (!Schema::hasColumn('items', 'item_code')) {
                $table->string('item_code')->nullable()->unique()->after('name');
            }
            if (!Schema::hasColumn('items', 'item_category_id')) {
                $table->foreignId('item_category_id')->nullable()->constrained('item_categories')->onDelete('set null')->after('item_code');
            }
            if (!Schema::hasColumn('items', 'item_type')) {
                $table->string('item_type')->nullable()->after('item_category_id');
            }
            if (!Schema::hasColumn('items', 'barcode')) {
                $table->string('barcode')->nullable()->unique()->after('item_type');
            }
            if (!Schema::hasColumn('items', 'description')) {
                $table->text('description')->nullable()->after('barcode');
            }
            if (!Schema::hasColumn('items', 'reorder_level')) {
                $table->decimal('reorder_level', 12, 2)->default(0)->after('description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropForeign(['item_category_id']);
            $table->dropColumn(['item_code', 'item_category_id', 'item_type', 'barcode', 'description', 'reorder_level']);
        });
    }
};

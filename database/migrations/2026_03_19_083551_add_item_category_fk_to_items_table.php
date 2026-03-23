<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('items') || !Schema::hasTable('item_categories')) {
            return;
        }

        if (Schema::hasColumn('items', 'item_category_id')) {
            Schema::table('items', function (Blueprint $table) {
                try {
                    $table->foreign('item_category_id')
                        ->references('id')
                        ->on('item_categories')
                        ->onDelete('set null');
                } catch (\Exception $e) {
                    // Ignore if FK already exists or cannot be created
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('items') && Schema::hasColumn('items', 'item_category_id')) {
            Schema::table('items', function (Blueprint $table) {
                try {
                    $table->dropForeign(['item_category_id']);
                } catch (\Exception $e) {
                    // Ignore if FK doesn't exist
                }
            });
        }
    }
};

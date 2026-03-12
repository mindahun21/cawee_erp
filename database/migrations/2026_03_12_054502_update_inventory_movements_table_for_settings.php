<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->foreignId('reason_id')->nullable()->constrained('inventory_movement_reasons')->nullOnDelete();
            $table->foreignId('status_id')->nullable()->constrained('inventory_movement_statuses')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropForeign(['reason_id']);
            $table->dropForeign(['status_id']);
            $table->dropColumn(['reason_id', 'status_id']);
        });
    }
};

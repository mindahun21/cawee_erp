<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_movements', 'movement_type')) {
                $table->string('movement_type')->nullable()->after('id'); // IN, OUT, TRANSFER
            }
            if (!Schema::hasColumn('inventory_movements', 'approved_by_id')) {
                $table->foreignId('approved_by_id')->nullable()->constrained('employees')->onDelete('set null')->after('employee_id');
            }
            if (!Schema::hasColumn('inventory_movements', 'attachments')) {
                $table->json('attachments')->nullable()->after('remarks');
            }
        });
    }

    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropForeign(['approved_by_id']);
            $table->dropColumn(['movement_type', 'approved_by_id', 'attachments']);
        });
    }
};

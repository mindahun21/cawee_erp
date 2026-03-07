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
        if (!Schema::hasColumn('assets', 'supplier_id')) {
            Schema::table('assets', function (Blueprint $table) {
                $table->foreignId('supplier_id')->nullable()->after('donor_id')->constrained('procurement_suppliers')->nullOnDelete();
            });
        }

        if (!Schema::hasColumn('inventory_movements', 'supplier_id')) {
            Schema::table('inventory_movements', function (Blueprint $table) {
                $table->foreignId('supplier_id')->nullable()->after('user_id')->constrained('procurement_suppliers')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            if (Schema::hasColumn('assets', 'supplier_id')) {
                // We might not want to drop it if it was there before, 
                // but for this migration's integrity we'll try to keep it simple.
                // Actually, safer NOT to drop if we didn't create it here, 
                // but Laravel's dropConstrainedForeignId is specific.
            }
        });

        Schema::table('inventory_movements', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_movements', 'supplier_id')) {
                $table->dropConstrainedForeignId('supplier_id');
            }
        });
    }
};

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
        Schema::table('procurement_goods_receipts', function (Blueprint $table) {
            $table->enum('status', [
                'Draft', 'Inspecting', 'Pending Approval', 'Approved', 'Accepted', 'Rejected', 'Partial'
            ])->default('Draft')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('procurement_goods_receipts', function (Blueprint $table) {
            $table->enum('status', [
                'Draft', 'Inspecting', 'Accepted', 'Rejected', 'Partial'
            ])->default('Draft')->change();
        });
    }
};

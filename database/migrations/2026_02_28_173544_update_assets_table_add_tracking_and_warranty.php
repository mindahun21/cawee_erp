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
        Schema::table('assets', function (Blueprint $table) {
            $table->string('qr_code')->nullable()->unique()->after('barcode');
            $table->string('rfid_tag')->nullable()->unique()->after('qr_code');
            $table->date('warranty_expiry_date')->nullable()->after('purchase_date');
            $table->json('contract_details')->nullable()->after('description');
            $table->string('depreciation_method')->default('straight-line')->after('residual_value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn([
                'qr_code',
                'rfid_tag',
                'warranty_expiry_date',
                'contract_details',
                'depreciation_method',
            ]);
        });
    }
};

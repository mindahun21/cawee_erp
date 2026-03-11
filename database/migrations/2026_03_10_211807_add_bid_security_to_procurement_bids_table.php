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
        Schema::table('procurement_bids', function (Blueprint $table) {
            $table->string('bid_security', 150)->nullable()->after('validity_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('procurement_bids', function (Blueprint $table) {
            $table->dropColumn('bid_security');
        });
    }
};

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
        Schema::create('procurement_contract_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('procurement_contracts', function (Blueprint $table) {
            $table->string('contract_type')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procurement_contract_types');

        Schema::table('procurement_contracts', function (Blueprint $table) {
            // Note: Cannot revert back to enum easily if dynamic values were added
            $table->string('contract_type', 255)->nullable()->change();
        });
    }
};

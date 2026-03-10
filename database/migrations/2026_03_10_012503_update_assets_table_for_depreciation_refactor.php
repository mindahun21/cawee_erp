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
            $table->foreignIdFor(\App\Models\Depreciation::class)->nullable()->constrained()->onDelete('set null');
            $table->dropColumn(['useful_life', 'residual_value', 'depreciation_method']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('depreciation_id');
            $table->integer('useful_life')->nullable();
            $table->decimal('residual_value', 15, 2)->nullable();
            $table->string('depreciation_method')->nullable();
        });
    }
};

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
        Schema::table('donations', function (Blueprint $table) {
            $table->string('receipt_number', 50)->unique()->nullable()->after('id');
            $table->text('notes')->nullable()->after('in_kind_description');
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])
                ->default('completed')
                ->after('notes');
            
            // Add index for faster receipt lookups
            $table->index('receipt_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->dropIndex(['receipt_number']);
            $table->dropColumn(['receipt_number', 'notes', 'status']);
        });
    }
};

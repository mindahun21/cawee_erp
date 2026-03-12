<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('depreciation_logs');
    }

    public function down(): void
    {
        Schema::create('depreciation_logs', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->onDelete('cascade');
            $table->date('period_date');
            $table->decimal('depreciation_amount', 15, 2);
            $table->decimal('book_value', 15, 2);
            $table->timestamps();
        });
    }
};

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
        Schema::create('warehouses', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->string('warehouse_code')->unique();
            $blueprint->string('name');
            $blueprint->integer('order')->nullable();
            $blueprint->text('address')->nullable();
            $blueprint->string('city')->nullable();
            $blueprint->string('province')->nullable();
            $blueprint->string('postal_code')->nullable();
            $blueprint->string('country')->nullable();
            $blueprint->text('note')->nullable();
            $blueprint->timestamps();
            $blueprint->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};

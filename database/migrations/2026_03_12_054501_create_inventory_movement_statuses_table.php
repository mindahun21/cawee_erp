<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('inventory_movement_statuses')) {
            Schema::create('inventory_movement_statuses', function (Blueprint $blueprint) {
                $blueprint->id();
                $blueprint->string('name')->unique();
                $blueprint->text('description')->nullable();
                $blueprint->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movement_statuses');
    }
};

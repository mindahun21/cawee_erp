<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_locations', function (Blueprint $table) {
            $table->id();
            $table->string('location_name');
            $table->text('address')->nullable();
            $table->enum('type', ['Head Office', 'Field Office', 'Factory', 'Bakery', 'Guesthouse']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_locations');
    }
};

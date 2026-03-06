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
        Schema::create('vehicle_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->onDelete('cascade');
            
            $table->string('plate_number')->unique();
            $table->string('chassis_number')->nullable();
            $table->string('motor_number')->nullable();
            $table->string('engine_size')->nullable();
            $table->string('fuel_type')->nullable(); // Diesel, Petrol, etc.
            $table->string('capacity')->nullable(); // e.g., 8 people, 5 tons
            $table->string('color')->nullable();
            $table->string('horsepower')->nullable();
            $table->string('year_manufactured')->nullable();
            $table->string('manufacturer')->nullable();
            
            // Insurance and Inspection
            $table->string('insurance_company')->nullable();
            $table->string('insurance_policy_no')->nullable();
            $table->date('insurance_expiration_date')->nullable();
            $table->date('technical_inspection_date')->nullable();
            $table->date('technical_inspection_expiration_date')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_details');
    }
};

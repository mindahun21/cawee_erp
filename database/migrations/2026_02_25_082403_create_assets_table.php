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
        if (Schema::hasTable('assets')) {
            return;
        }

        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_category_id')->constrained()->onDelete('cascade');
            $table->foreignId('location_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('department_id')->nullable()->constrained()->onDelete('set null');
            
            $table->string('name');
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('barcode')->nullable()->unique();
            $table->string('identification_type')->nullable(); // Barcode, QR Code, RFID, Serial Number
            $table->string('acquisition_type')->nullable(); // Purchase, Donation, Lease
            
            $table->decimal('purchase_cost', 15, 2)->default(0);
            $table->date('purchase_date')->nullable();
            $table->integer('useful_life')->nullable(); // in years
            $table->decimal('residual_value', 15, 2)->default(0);
            
            $table->string('status')->default('available'); // available, assigned, maintenance, disposed, lost
            $table->string('condition')->nullable(); // New, Good, Fair, Poor, Broken
            $table->text('description')->nullable();
            $table->boolean('is_fixed_asset')->default(true);
            $table->integer('quantity')->default(1); // for consumables
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};

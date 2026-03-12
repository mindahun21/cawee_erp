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
        Schema::create('vehicle_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('vehicle_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('color')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('plate_number')->unique();
            $table->foreignId('vehicle_type_id')->constrained();
            $table->string('country_manufacturer')->nullable();
            $table->string('model')->nullable();
            $table->string('year_manufactured')->nullable();
            $table->string('manufacturer')->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained('procurement_suppliers');
            $table->string('acquisition_status')->nullable(); // New, Used, Donation
            $table->date('purchase_date')->nullable();
            $table->decimal('kms_driven_at_purchase', 15, 2)->nullable();
            $table->decimal('purchase_price', 15, 2)->nullable();
            $table->string('currency')->nullable();
            $table->string('chassis_number')->nullable();
            $table->string('motor_number')->nullable();
            $table->string('color')->nullable();
            $table->string('horsepower')->nullable();
            $table->string('general_weight')->nullable();
            $table->string('single_weight')->nullable();
            $table->string('engine_size_cc')->nullable();
            $table->string('capacity')->nullable();
            $table->string('fuel_type')->nullable();
            $table->integer('number_of_cylinders')->nullable();
            
            // Insurance/Inspection
            $table->string('general_insurance')->nullable();
            $table->string('third_party_insurance')->nullable();
            $table->string('trade_license_number')->nullable();
            $table->date('latest_technical_inspection_date')->nullable();
            $table->date('latest_technical_inspection_expiry')->nullable();
            $table->date('latest_general_inspection_date')->nullable();
            $table->date('latest_general_inspection_expiry')->nullable();
            $table->date('latest_third_party_inspection_date')->nullable();
            $table->date('insurance_renewal_date')->nullable();

            $table->foreignId('vehicle_status_id')->constrained()->onDelete('cascade');
            $table->foreignId('current_location_id')->nullable()->constrained('locations')->onDelete('set null');
            
            $table->text('remarks')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('vehicle_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete(); // Driver/Staff
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->date('assigned_date');
            $table->date('return_date')->nullable();
            $table->string('status')->default('Active'); // Active, Completed, Cancelled
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('vehicle_maintenances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->date('service_date');
            $table->string('service_type')->nullable();
            $table->text('description')->nullable();
            $table->decimal('cost', 15, 2)->default(0);
            $table->date('next_service_date')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('vehicle_fuel_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->decimal('quantity', 15, 2);
            $table->decimal('cost', 15, 2)->default(0);
            $table->decimal('odometer_reading', 15, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_fuel_logs');
        Schema::dropIfExists('vehicle_maintenances');
        Schema::dropIfExists('vehicle_assignments');
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('vehicle_statuses');
        Schema::dropIfExists('vehicle_types');
    }
};

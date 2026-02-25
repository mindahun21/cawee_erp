<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('travel_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->enum('travel_type', ['Field', 'Conference', 'Other']);
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('per_diem_amount', 10, 2)->default(0);
            $table->boolean('vehicle_required')->default(false);
            $table->enum('approval_status', ['Pending', 'Approved', 'Rejected'])->default('Pending');
            $table->boolean('report_submitted')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_requests');
    }
};

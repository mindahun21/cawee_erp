<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Travel Advance Request Form (TARF)
        Schema::create('travel_advances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('hr_projects')->nullOnDelete();
            $table->string('payment_center', 150)->nullable();
            $table->string('place_of_travel', 150);
            $table->text('purpose')->nullable();
            $table->date('depart_date');
            $table->date('return_date');
            $table->unsignedSmallInteger('planned_days');
            $table->decimal('per_diem_rate', 10, 2)->default(0);
            $table->decimal('per_diem_amount', 10, 2)->default(0);
            $table->decimal('accommodation_amount', 10, 2)->default(0);
            $table->decimal('transport_amount', 10, 2)->default(0);
            $table->decimal('other_amount', 10, 2)->default(0);
            $table->string('other_description', 255)->nullable();
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->string('budget_code', 50)->nullable();
            $table->string('budget_title', 150)->nullable();
            $table->enum('status', ['Draft', 'Submitted', 'Approved', 'Settled', 'Rejected'])->default('Draft');

            // TARF approval
            $table->foreignId('checked_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamp('checked_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('authorized_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamp('authorized_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Travel Advance Clearance Form (TACF) — settlement
        Schema::create('travel_advance_clearances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('travel_advance_id')->constrained('travel_advances')->cascadeOnDelete();
            $table->date('actual_depart_date');
            $table->date('actual_return_date');
            $table->unsignedSmallInteger('actual_days_spent');
            $table->decimal('per_diem_settled', 10, 2)->default(0);
            $table->decimal('accommodation_settled', 10, 2)->default(0);
            $table->decimal('transport_settled', 10, 2)->default(0);
            $table->decimal('other_settled', 10, 2)->default(0);
            $table->decimal('total_settled', 10, 2)->default(0);
            $table->decimal('advance_received', 10, 2)->default(0);
            $table->decimal('net_due', 10, 2)->default(0); // positive = org owes; negative = staff owes
            $table->string('pv_number', 50)->nullable();
            $table->string('rv_number', 50)->nullable();
            $table->foreignId('checked_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamp('checked_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_advance_clearances');
        Schema::dropIfExists('travel_advances');
    }
};

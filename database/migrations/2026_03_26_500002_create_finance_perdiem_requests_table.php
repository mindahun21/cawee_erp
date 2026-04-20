<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_perdiem_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 30)->unique()->comment('Auto: PDR-2026-0001');
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('perdiem_type_id')->constrained('finance_perdiem_types');

            $table->string('travel_destination', 200);
            $table->string('purpose', 500);
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedSmallInteger('days_count')->default(1);

            $table->decimal('daily_rate', 18, 2)->default(0);
            $table->decimal('total_requested', 18, 2)->default(0);
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();

            // 4-dimension coding
            $table->string('activity_code', 50)->nullable();
            $table->foreignId('project_id')->nullable()->constrained('hr_projects')->nullOnDelete();
            $table->foreignId('cost_center_id')->nullable()->constrained('finance_cost_centers')->nullOnDelete();
            $table->foreignId('donor_id')->nullable()->constrained('donors')->nullOnDelete();

            // Advance
            $table->boolean('advance_requested')->default(false);
            $table->decimal('amount_advanced', 18, 2)->default(0);

            // Workflow
            $table->enum('status', [
                'draft', 'pending', 'approved', 'rejected', 'settled', 'cancelled',
            ])->default('draft');
            $table->unsignedTinyInteger('approval_stage')->default(1);

            $table->foreignId('prepared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['employee_id', 'status']);
            $table->index(['start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_perdiem_requests');
    }
};

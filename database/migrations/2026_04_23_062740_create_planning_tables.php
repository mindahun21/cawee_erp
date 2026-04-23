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
        // Plans Table
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('objectives')->nullable();
            $table->text('outcomes')->nullable();
            $table->enum('type', ['annual', 'monthly', 'weekly', 'activity'])->default('annual');
            $table->foreignId('parent_id')->nullable()->constrained('plans')->onDelete('cascade');
            $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('set null');
            $table->foreignId('project_id')->nullable()->constrained('projects')->onDelete('set null');
            $table->foreignId('budget_id')->nullable()->constrained('procurement_budgets')->onDelete('set null');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->json('attachments')->nullable();
            $table->integer('progress_percentage')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        // Tasks Table
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('plans')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->onDelete('set null');
            $table->date('deadline')->nullable();
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending');
            $table->integer('progress_percentage')->default(0);
            $table->json('attachments')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // KPIs Table
        Schema::create('planning_kpis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('plans')->onDelete('cascade');
            $table->string('indicator_name');
            $table->decimal('target_value', 15, 2)->default(0);
            $table->decimal('actual_value', 15, 2)->default(0);
            $table->string('unit')->nullable();
            $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });

        // Resource Allocation Table (Coupling Strategy: Polymorphic)
        Schema::create('plan_resource_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('plans')->onDelete('cascade');
            $table->morphs('resourceable'); // item, employee, budget link
            $table->decimal('quantity', 15, 2)->default(1);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_resource_allocations');
        Schema::dropIfExists('planning_kpis');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('plans');
    }
};

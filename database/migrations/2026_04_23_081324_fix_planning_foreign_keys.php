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
        Schema::disableForeignKeyConstraints();

        // Recreate Plans table with correct references
        Schema::dropIfExists('plans');
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('objectives')->nullable();
            $table->text('outcomes')->nullable();
            $table->enum('type', ['annual', 'monthly', 'weekly', 'activity'])->default('annual');
            $table->foreignId('parent_id')->nullable()->constrained('plans')->onDelete('cascade');
            $table->foreignId('department_id')->nullable()->constrained('hr_departments')->onDelete('set null');
            $table->foreignId('project_id')->nullable()->constrained('hr_projects')->onDelete('set null');
            $table->foreignId('budget_id')->nullable()->constrained('procurement_budgets')->onDelete('set null');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->json('attachments')->nullable();
            $table->integer('progress_percentage')->default(0);
            $table->string('status')->default('draft');
            $table->timestamps();
            $table->softDeletes();
        });

        // Recreate KPIs table with correct references
        Schema::dropIfExists('planning_kpis');
        Schema::create('planning_kpis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('plans')->onDelete('cascade');
            $table->string('indicator_name');
            $table->decimal('target_value', 15, 2)->default(0);
            $table->decimal('actual_value', 15, 2)->default(0);
            $table->string('unit')->nullable();
            $table->foreignId('department_id')->nullable()->constrained('hr_departments')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            //
        });
    }
};

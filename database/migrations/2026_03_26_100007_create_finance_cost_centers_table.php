<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_cost_centers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 120);
            $table->enum('type', [
                'head_office',
                'regional_office',
                'project',
                'donor_restricted',
                'shared_services',
            ]);
            // Parent cost center for hierarchical grouping (nullable = root)
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('finance_cost_centers')
                ->nullOnDelete();
            // Optional link to the HR project master
            $table->foreignId('hr_project_id')
                ->nullable()
                ->constrained('hr_projects')
                ->nullOnDelete();
            // Optional link to a specific donor (for donor-restricted cost centers)
            $table->foreignId('donor_id')
                ->nullable()
                ->constrained('donors')
                ->nullOnDelete();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_cost_centers');
    }
};

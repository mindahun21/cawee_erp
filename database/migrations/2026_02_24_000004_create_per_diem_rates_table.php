<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('per_diem_rates', function (Blueprint $table) {
            $table->id();
            $table->string('label', 150); // e.g. "Senior Staff - Field Office"
            $table->string('position_pattern', 150)->nullable(); // e.g. "Director", or null = all
            $table->foreignId('project_id')->nullable()->constrained('hr_projects')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('hr_locations')->nullOnDelete();
            $table->decimal('rate_per_day', 10, 2);
            $table->string('currency', 10)->default('ETB');
            $table->boolean('is_active')->default(true);
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('per_diem_rates');
    }
};

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
        Schema::create('planning_reports', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('type'); // annual, monthly, kpi_summary
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->foreignId('department_id')->nullable()->constrained();
            $table->json('parameters')->nullable();
            $table->string('file_path')->nullable();
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->foreignId('generated_by_id')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('planning_reports');
    }
};

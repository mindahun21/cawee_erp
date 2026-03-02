<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_projects', function (Blueprint $table) {
            $table->id();
            $table->string('project_name');
            $table->string('project_code')->unique();
            $table->foreignId('location_id')->nullable()->constrained('hr_locations')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_projects');
    }
};

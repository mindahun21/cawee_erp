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
        Schema::table('hr_job_positions', function (Blueprint $table) {
            if (!Schema::hasColumn('hr_job_positions', 'description')) {
                $table->text('description')->nullable()->after('grade_id');
                $table->text('requirements')->nullable()->after('description');
                $table->decimal('salary_min', 15, 2)->nullable()->after('requirements');
                $table->decimal('salary_max', 15, 2)->nullable()->after('salary_min');
                $table->integer('vacancy_count')->default(1)->after('salary_max');
                $table->boolean('is_active')->default(true)->after('vacancy_count');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_job_positions', function (Blueprint $table) {
            $table->dropColumn([
                'description', 
                'requirements', 
                'salary_min', 
                'salary_max', 
                'vacancy_count', 
                'is_active'
            ]);
        });
    }
};

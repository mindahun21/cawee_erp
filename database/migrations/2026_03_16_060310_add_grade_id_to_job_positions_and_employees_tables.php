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
            $table->dropColumn('grade');
            $table->foreignId('grade_id')->nullable()->constrained('hr_grades')->nullOnDelete()->after('title');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('grade');
            $table->foreignId('grade_id')->nullable()->constrained('hr_grades')->nullOnDelete()->after('salary_grade_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('grade_id');
            $table->string('grade', 20)->nullable();
        });

        Schema::table('hr_job_positions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('grade_id');
            $table->string('grade', 20)->nullable();
        });
    }
};

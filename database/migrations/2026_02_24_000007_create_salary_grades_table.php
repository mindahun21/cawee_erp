<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Salary grades table (Grade I to IV, Steps 1-15)
        Schema::create('salary_grades', function (Blueprint $table) {
            $table->id();
            $table->string('grade', 10);   // I, II, III, IV
            $table->unsignedTinyInteger('step'); // 1-15
            $table->decimal('basic_salary', 10, 2);
            $table->decimal('transport_allowance', 10, 2)->default(0);
            $table->decimal('house_allowance', 10, 2)->default(0);
            $table->decimal('communications_allowance', 10, 2)->default(0);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['grade', 'step', 'effective_from']);
        });

        // Add grade/step columns to employees for future use
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('salary_grade_id')->nullable()->constrained('salary_grades')->nullOnDelete()->after('basic_salary');
            $table->string('grade', 10)->nullable()->after('salary_grade_id');
            $table->unsignedTinyInteger('step')->nullable()->after('grade');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['salary_grade_id']);
            $table->dropColumn(['salary_grade_id', 'grade', 'step']);
        });
        Schema::dropIfExists('salary_grades');
    }
};

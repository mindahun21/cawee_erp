<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();

            // Personal info
            $table->string('first_name');
            $table->string('last_name');
            $table->enum('gender', ['M', 'F']);
            $table->date('date_of_birth')->nullable();
            $table->string('national_id')->nullable();
            $table->string('tin')->nullable();
            $table->string('pension_id')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('email')->nullable();

            // Education & position
            $table->string('education_level', 100)->nullable();
            $table->string('field_of_study', 100)->nullable();

            $table->json('extra_attributes')->nullable(); // For dynamic fields

            // Employment details
            $table->string('position')->nullable();
            $table->enum('employment_type', ['Contract', 'Temporary', 'Consultancy', 'Other'])->nullable();

            // Employment dates
            $table->date('date_of_employment')->nullable();
            $table->date('date_transferred')->nullable();
            $table->date('date_resigned')->nullable();

            // Compensation
            $table->decimal('basic_salary', 10, 2)->default(0);
            $table->decimal('transport_allowance', 10, 2)->default(0);
            $table->decimal('house_allowance', 10, 2)->default(0);
            $table->decimal('communication_allowance', 10, 2)->default(0);
            $table->decimal('overtime_allowance', 10, 2)->default(0);
            $table->decimal('incentive', 10, 2)->default(0);
            $table->decimal('other_allowances', 10, 2)->default(0);

            // Bank accounts
            $table->string('bank_account_awash')->nullable();
            $table->string('bank_account_orocoop')->nullable();
            $table->string('bank_account_other')->nullable();

            $table->text('remarks')->nullable();

            // Foreign keys
            $table->foreignId('location_id')->nullable()->constrained('hr_locations')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('hr_projects')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};

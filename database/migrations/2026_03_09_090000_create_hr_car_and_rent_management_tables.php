<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_setting_options', function (Blueprint $table) {
            $table->id();
            $table->string('category', 100);
            $table->string('code', 100)->nullable();
            $table->string('label', 150);
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['category', 'label']);
            $table->index(['category', 'is_active']);
        });

        Schema::create('hr_landlords', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('phone', 50)->nullable();
            $table->string('email', 150)->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('name');
        });

        Schema::create('hr_branches', function (Blueprint $table) {
            $table->id();
            $table->string('branch_name', 150);
            $table->string('branch_code', 50)->nullable()->unique();
            $table->foreignId('location_id')->nullable()->constrained('hr_locations')->nullOnDelete();
            $table->foreignId('branch_type_option_id')->nullable()->constrained('hr_setting_options')->nullOnDelete();
            $table->string('proposed_office', 150)->nullable();
            $table->text('address')->nullable();
            $table->enum('status', ['Requested', 'Pending Agreement', 'Active', 'Closed'])->default('Requested');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('hr_office_rent_agreements', function (Blueprint $table) {
            $table->id();
            $table->string('agreement_code', 60)->unique();
            $table->foreignId('branch_id')->constrained('hr_branches')->cascadeOnDelete();
            $table->foreignId('landlord_id')->nullable()->constrained('hr_landlords')->nullOnDelete();
            $table->foreignId('payment_cycle_option_id')->nullable()->constrained('hr_setting_options')->nullOnDelete();
            $table->text('property_address');
            $table->decimal('monthly_rent', 12, 2)->default(0);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('contract_document_path')->nullable();
            $table->enum('status', ['Draft', 'Pending Legal', 'Approved', 'Rejected', 'Active', 'Expired', 'Terminated'])->default('Draft');
            $table->foreignId('legal_reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('legal_reviewed_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'end_date']);
        });

        Schema::create('hr_agreement_renewals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_rent_agreement_id')->constrained('hr_office_rent_agreements')->cascadeOnDelete();
            $table->foreignId('decision_option_id')->nullable()->constrained('hr_setting_options')->nullOnDelete();
            $table->decimal('new_monthly_rent', 12, 2)->nullable();
            $table->date('new_start_date')->nullable();
            $table->date('new_end_date')->nullable();
            $table->enum('status', ['Pending', 'Approved', 'Rejected', 'Applied'])->default('Pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('hr_vehicle_service_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->foreignId('service_type_option_id')->nullable()->constrained('hr_setting_options')->nullOnDelete();
            $table->foreignId('urgency_option_id')->nullable()->constrained('hr_setting_options')->nullOnDelete();
            $table->foreignId('provider_option_id')->nullable()->constrained('hr_setting_options')->nullOnDelete();
            $table->text('problem_description');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('requested_at')->nullable();
            $table->enum('status', ['Pending', 'Approved', 'In Service', 'Completed', 'Rejected'])->default('Pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('service_report_path')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'created_at']);
        });

        Schema::create('hr_vehicle_maintenance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->foreignId('service_request_id')->nullable()->constrained('hr_vehicle_service_requests')->nullOnDelete();
            $table->foreignId('service_type_option_id')->nullable()->constrained('hr_setting_options')->nullOnDelete();
            $table->foreignId('provider_option_id')->nullable()->constrained('hr_setting_options')->nullOnDelete();
            $table->date('service_date');
            $table->unsignedInteger('odometer_km')->nullable();
            $table->decimal('cost', 12, 2)->default(0);
            $table->unsignedInteger('next_service_odometer')->nullable();
            $table->date('next_service_date')->nullable();
            $table->string('report_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['next_service_date', 'service_date'], 'hr_veh_maint_service_dates_idx');
        });

        Schema::create('hr_vehicle_licenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->string('license_number', 100)->nullable();
            $table->date('bolo_issue_date')->nullable();
            $table->date('bolo_expiry_date');
            $table->string('receipt_path')->nullable();
            $table->enum('status', ['Valid', 'Expiring', 'Expired'])->default('Valid');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('bolo_expiry_date');
        });

        Schema::create('hr_vehicle_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->date('inspection_date')->nullable();
            $table->date('inspection_expiry_date');
            $table->string('certificate_path')->nullable();
            $table->enum('status', ['Valid', 'Expiring', 'Expired'])->default('Valid');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('inspection_expiry_date');
        });

        Schema::create('hr_branch_utilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('hr_branches')->cascadeOnDelete();
            $table->foreignId('utility_type_option_id')->nullable()->constrained('hr_setting_options')->nullOnDelete();
            $table->string('provider', 150)->nullable();
            $table->string('account_number', 100)->nullable();
            $table->foreignId('payment_cycle_option_id')->nullable()->constrained('hr_setting_options')->nullOnDelete();
            $table->decimal('estimated_amount', 12, 2)->default(0);
            $table->date('next_due_date')->nullable();
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'next_due_date']);
        });

        Schema::create('hr_utility_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_utility_id')->constrained('hr_branch_utilities')->cascadeOnDelete();
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->date('due_date');
            $table->decimal('amount', 12, 2);
            $table->enum('status', ['Pending', 'Paid', 'Overdue'])->default('Pending');
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_utility_payments');
        Schema::dropIfExists('hr_branch_utilities');
        Schema::dropIfExists('hr_vehicle_inspections');
        Schema::dropIfExists('hr_vehicle_licenses');
        Schema::dropIfExists('hr_vehicle_maintenance_records');
        Schema::dropIfExists('hr_vehicle_service_requests');
        Schema::dropIfExists('hr_agreement_renewals');
        Schema::dropIfExists('hr_office_rent_agreements');
        Schema::dropIfExists('hr_branches');
        Schema::dropIfExists('hr_landlords');
        Schema::dropIfExists('hr_setting_options');
    }
};


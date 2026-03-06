<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // ─────────────────────────────────────────────────────────────────
    //  Procurement Module — Part 2
    //  Tables:
    //    1. procurement_contracts          (Section 6 — Award & Contracting)
    //    2. procurement_contract_versions  (versioned storage)
    // ─────────────────────────────────────────────────────────────────

    public function up(): void
    {
        // ── Contracts ─────────────────────────────────────────────────
        Schema::create('procurement_contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number', 50)->unique();

            // Links back into P2P chain
            $table->foreignId('tender_id')->nullable()->constrained('procurement_tenders')->nullOnDelete();
            $table->foreignId('bid_id')->nullable()->constrained('procurement_bids')->nullOnDelete();
            $table->foreignId('purchase_order_id')->nullable()->constrained('procurement_purchase_orders')->nullOnDelete();
            $table->foreignId('supplier_id')->constrained('procurement_suppliers')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();

            $table->string('title', 300);
            $table->text('description')->nullable();

            $table->enum('contract_type', [
                'Goods Supply', 'Services', 'Works', 'Consultancy', 'Framework', 'Other',
            ])->default('Goods Supply');

            $table->enum('status', [
                'Draft', 'Pending Signature', 'Active', 'Suspended', 'Expired', 'Terminated', 'Completed',
            ])->default('Draft');

            // Key dates
            $table->date('effective_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->date('supplier_signed_at')->nullable();
            $table->date('org_signed_at')->nullable();

            // Financials
            $table->string('currency', 10)->default('ETB');
            $table->decimal('contract_value', 15, 2)->default(0);
            $table->decimal('advance_payment_percentage', 5, 2)->default(0);
            $table->string('payment_terms', 200)->nullable();

            // Parties
            $table->string('org_signatory_name', 150)->nullable();
            $table->string('org_signatory_title', 100)->nullable();
            $table->string('supplier_contact_person', 150)->nullable();

            // Approval
            $table->enum('approval_status', ['Pending', 'Approved', 'Rejected'])->default('Pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_remarks')->nullable();

            $table->text('special_conditions')->nullable();
            $table->text('attachments')->nullable()->comment('JSON array of file paths (signed PDFs, schedules, ToR)');

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('expiry_date');
            $table->index('supplier_id');
        });

        // ── Contract Versions ─────────────────────────────────────────
        // Every amendment/revision is stored as a new version row
        Schema::create('procurement_contract_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('procurement_contracts')->cascadeOnDelete();
            $table->unsignedSmallInteger('version_number')->default(1);
            $table->string('change_summary', 500);
            $table->decimal('amended_value', 15, 2)->nullable()->comment('New contract value after amendment');
            $table->date('amendment_date');
            $table->foreignId('amended_by')->constrained('users')->cascadeOnDelete();
            $table->text('document')->nullable()->comment('File path of the amendment document');
            $table->timestamps();

            $table->unique(['contract_id', 'version_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procurement_contract_versions');
        Schema::dropIfExists('procurement_contracts');
    }
};

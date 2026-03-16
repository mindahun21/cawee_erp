<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procurement_approval_records', function (Blueprint $table) {
            $table->id();

            // Polymorphic — works for Invoice, PurchaseOrder, Payment, etc.
            $table->morphs('approvable');                    // approvable_type + approvable_id

            // Snapshot of the stage at time of creation (so records survive stage config changes)
            $table->foreignId('stage_id')
                ->nullable()
                ->constrained('procurement_approval_stages')
                ->nullOnDelete();
            $table->unsignedTinyInteger('stage_order');
            $table->string('stage_name');                    // denormalised
            $table->string('required_role');                 // denormalised

            // Decision
            $table->enum('status', ['Pending', 'Approved', 'Rejected'])->default('Pending');
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            // One record per document per stage
            $table->unique(['approvable_type', 'approvable_id', 'stage_order'], 'proc_approval_rec_type_id_stage_unique');

            $table->index(['approvable_type', 'approvable_id', 'status'], 'proc_approval_rec_type_id_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procurement_approval_records');
    }
};

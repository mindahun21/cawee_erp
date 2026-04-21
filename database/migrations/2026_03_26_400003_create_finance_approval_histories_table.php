<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Polymorphic approval trail — used by ALL finance documents:
        // PaymentRequisitions, PaymentVouchers, CRVs, PerDiem, etc.
        Schema::create('finance_approval_histories', function (Blueprint $table) {
            $table->id();

            // Polymorphic reference to any approvable finance document
            $table->string('approvable_type', 150)->comment('FQCN of the document model');
            $table->unsignedBigInteger('approvable_id');

            $table->unsignedTinyInteger('stage_number')->default(1)->comment('Which approval level (1=Finance Officer, 2=Manager, …)');
            $table->string('stage_name', 80)->comment('e.g. "Finance Manager Approval"');

            $table->enum('action', ['approved', 'rejected', 'returned', 'noted', 'forwarded']);

            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();

            $table->text('comments')->nullable();

            $table->timestamp('actioned_at')->useCurrent();

            $table->string('previous_status', 30)->nullable();
            $table->string('new_status', 30)->nullable();

            $table->timestamps();

            $table->index(['approvable_type', 'approvable_id'], 'ah_poly_idx');
            $table->index(['actor_id', 'actioned_at'], 'ah_actor_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_approval_histories');
    }
};

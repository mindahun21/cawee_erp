<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * hr_delegations
 * Records temporary delegation of duties: one employee covers another's role/responsibilities
 * while they are on leave, travel, or otherwise unavailable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_delegations', function (Blueprint $table) {
            $table->id();

            // The employee delegating their duties (the one going away)
            $table->foreignId('delegator_id')->constrained('employees')->cascadeOnDelete();

            // The employee receiving the duties
            $table->foreignId('delegate_id')->constrained('employees')->cascadeOnDelete();

            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('subject', 200);
            $table->text('scope')->nullable();       // What duties are being delegated
            $table->string('reason', 200)->nullable(); // Leave / Travel / Other
            $table->string('status', 30)->default('Active'); // Active, Completed, Cancelled

            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reference_number', 50)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_delegations');
    }
};

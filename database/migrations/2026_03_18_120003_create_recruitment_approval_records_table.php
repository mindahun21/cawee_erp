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
        Schema::create('recruitment_approval_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('submission_cycle')->default(1);
            $table->morphs('approvable');
            $table->foreignId('stage_id')->constrained('recruitment_approval_stages')->cascadeOnDelete();
            $table->unsignedTinyInteger('stage_order');
            $table->string('stage_name');
            $table->string('required_role');
            $table->string('status')->default('Pending'); // Pending, Approved, Rejected
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->datetime('decided_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(
                ['approvable_type', 'approvable_id', 'submission_cycle', 'stage_order'],
                'approval_records_morph_cycle_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recruitment_approval_records');
    }
};

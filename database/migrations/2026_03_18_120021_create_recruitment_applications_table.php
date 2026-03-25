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
        Schema::create('recruitment_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')
                  ->constrained('recruitment_candidates')
                  ->cascadeOnDelete();
            $table->foreignId('campaign_id')
                  ->constrained('recruitment_campaigns')
                  ->cascadeOnDelete();
            $table->foreignId('channel_id')
                  ->nullable()
                  ->constrained('recruitment_channels')
                  ->nullOnDelete();
            $table->text('cover_letter')->nullable();
            $table->string('status')->default('applied');
            // State machine — NEVER set directly, always via RecruitmentApplicationService::transition()
            // applied | under_review | shortlisted | interview_scheduled |
            // offer_pending | offer_accepted | offer_declined | hired | rejected | withdrawn
            $table->timestamp('applied_at')->useCurrent();
            $table->foreignId('reviewed_by')
                  ->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('shortlisted_by')
                  ->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            // INTERNAL — never shown to candidate
            $table->text('internal_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        
            $table->unique(['candidate_id', 'campaign_id']);
            // One application per candidate per campaign
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recruitment_applications');
    }
};

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
        Schema::create('recruitment_campaign_job_position', function (Blueprint $table) {
            $table->foreignId('campaign_id')->constrained('recruitment_campaigns')->cascadeOnDelete();
            $table->foreignId('job_position_id')->constrained('hr_job_positions')->cascadeOnDelete();
            $table->primary(['campaign_id', 'job_position_id'], 'campaign_job_pos_primary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recruitment_campaign_job_position');
    }
};

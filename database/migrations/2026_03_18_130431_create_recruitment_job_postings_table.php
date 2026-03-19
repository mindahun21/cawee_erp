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
        Schema::create('recruitment_job_postings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_position_id')->constrained('hr_job_positions')->cascadeOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained('recruitment_campaigns')->nullOnDelete();
            $table->foreignId('recruitment_plan_id')->nullable()->constrained('recruitment_plans')->nullOnDelete();
            $table->foreignId('channel_id')->nullable()->constrained('recruitment_channels')->nullOnDelete();
            $table->string('title');
            $table->text('description');
            $table->text('requirements')->nullable();
            $table->string('location')->nullable();
            $table->string('employment_type')->default('full_time'); // full_time | part_time | contract | internship
            $table->date('posted_date');
            $table->date('closing_date')->nullable();
            $table->string('status')->default('draft');  // draft | published | closed | on_hold
            $table->boolean('is_public')->default(true);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'is_public']);
            $table->index('job_position_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recruitment_job_postings');
    }
};

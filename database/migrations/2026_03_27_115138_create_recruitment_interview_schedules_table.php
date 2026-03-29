<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment_interview_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('recruitment_campaigns')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedTinyInteger('round')->nullable();
            $table->date('interview_date');
            $table->time('from_time');
            $table->time('to_time');
            $table->string('location');
            $table->foreignId('evaluation_template_id')->nullable()->constrained('recruitment_evaluation_form_templates')->nullOnDelete();
            $table->string('interview_type')->default('in_person'); // in_person | online | hybrid | telephone
            $table->string('status')->default('draft');
            $table->foreignId('approval_workflow_id')->nullable()->constrained('recruitment_approval_workflows')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['campaign_id', 'status']);
            $table->index('interview_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment_interview_schedules');
    }
};

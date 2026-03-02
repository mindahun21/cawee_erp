<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('me_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('table_name');
            $table->unsignedBigInteger('record_id');
            $table->enum('action', ['create', 'update', 'delete']);
            $table->json('changes')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['table_name', 'record_id'], 'me_audit_table_record_idx');
        });

        Schema::create('me_surveys', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['baseline', 'midline', 'endline', 'weekly']);
            $table->string('title');
            $table->date('period_start');
            $table->date('period_end');
            $table->boolean('is_active')->default(true);
        });

        Schema::create('me_survey_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained('me_surveys')->cascadeOnDelete();
            $table->text('question_text');
            $table->enum('question_type', ['text', 'number', 'choice', 'multi_choice', 'rating']);
            $table->json('options')->nullable();
            $table->boolean('is_required')->default(false);
        });

        Schema::create('me_survey_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained('me_surveys')->cascadeOnDelete();
            $table->timestamp('submitted_at')->useCurrent();
            $table->string('respondent_code')->nullable();
            $table->string('location')->nullable();
        });

        Schema::create('me_survey_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('response_id')->constrained('me_survey_responses')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('me_survey_questions')->cascadeOnDelete();
            $table->text('answer_text')->nullable();
            $table->decimal('answer_number', 14, 2)->nullable();
            $table->json('answer_json')->nullable();

            $table->unique(['response_id', 'question_id'], 'me_survey_answer_unique');
        });

        Schema::create('me_beneficiary_feedback', function (Blueprint $table) {
            $table->id();
            $table->timestamp('submitted_at')->useCurrent();
            $table->string('location')->nullable();
            $table->enum('sentiment', ['positive', 'neutral', 'negative']);
            $table->text('comment');
            $table->json('metadata')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('me_beneficiary_feedback');
        Schema::dropIfExists('me_survey_answers');
        Schema::dropIfExists('me_survey_responses');
        Schema::dropIfExists('me_survey_questions');
        Schema::dropIfExists('me_surveys');
        Schema::dropIfExists('me_audit_logs');
    }
};

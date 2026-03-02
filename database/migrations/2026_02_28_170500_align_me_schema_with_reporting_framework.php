<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('me_projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('project_code')->unique();
            $table->text('description')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
        });

        Schema::create('me_reporting_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('me_projects')->cascadeOnDelete();
            $table->enum('type', ['weekly', 'baseline', 'midline', 'endline']);
            $table->date('start_date');
            $table->date('end_date');
            $table->string('label');
            $table->boolean('is_locked')->default(false);
            $table->timestamps();

            $table->index(['project_id', 'type'], 'me_reporting_period_project_type_idx');
            $table->index(['start_date', 'end_date'], 'me_reporting_period_window_idx');
        });

        Schema::create('me_locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('me_locations')->nullOnDelete();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->timestamps();
        });

        Schema::table('me_indicators', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('id')->constrained('me_projects')->nullOnDelete();
            $table->string('name_local')->nullable()->after('name');
            $table->enum('direction', ['higher_is_better', 'lower_is_better'])->default('higher_is_better')->after('unit');
            $table->enum('data_type', ['number', 'integer', 'percent', 'currency', 'text'])->default('number')->after('direction');

            $table->index(['project_id', 'is_active'], 'me_indicators_project_active_idx');
        });

        Schema::table('me_indicator_targets', function (Blueprint $table) {
            $table->foreignId('reporting_period_id')->nullable()->after('indicator_id')->constrained('me_reporting_periods')->nullOnDelete();
            $table->decimal('target_min', 14, 2)->nullable()->after('target_value');
            $table->decimal('target_max', 14, 2)->nullable()->after('target_min');
            $table->text('notes')->nullable()->after('target_max');
        });

        Schema::table('me_indicator_reports', function (Blueprint $table) {
            $table->foreignId('reporting_period_id')->nullable()->after('indicator_id')->constrained('me_reporting_periods')->nullOnDelete();
            $table->enum('source', ['manual', 'import', 'survey', 'api'])->default('manual')->after('actual_value');
            $table->foreignId('entered_by')->nullable()->after('source')->constrained('users')->nullOnDelete();
            $table->timestamp('entered_at')->nullable()->after('entered_by');
            $table->text('comment')->nullable()->after('notes');
            $table->text('actual_text')->nullable()->after('actual_value');
        });

        Schema::create('me_indicator_target_disaggregations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('target_id')->constrained('me_indicator_targets')->cascadeOnDelete();
            $table->foreignId('disaggregation_option_id')->constrained('me_disaggregation_options')->cascadeOnDelete();
            $table->decimal('value', 14, 2);
            $table->timestamps();

            $table->unique(['target_id', 'disaggregation_option_id'], 'me_target_disagg_unique');
        });

        Schema::table('me_alert_rules', function (Blueprint $table) {
            $table->foreignId('indicator_id')->nullable()->after('id')->constrained('me_indicators')->cascadeOnDelete();
            $table->enum('rule_type', ['below_percent_of_target', 'below_target', 'above_target', 'outside_range'])
                ->nullable()
                ->after('condition');
            $table->decimal('threshold', 10, 2)->nullable()->after('rule_type');
            $table->enum('severity', ['info', 'warning', 'critical'])->default('warning')->after('threshold');
        });

        Schema::table('me_alerts', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('id')->constrained('me_projects')->nullOnDelete();
            $table->foreignId('reporting_period_id')->nullable()->after('report_id')->constrained('me_reporting_periods')->nullOnDelete();
            $table->enum('status', ['open', 'acknowledged', 'resolved'])->default('open')->after('severity');
            $table->foreignId('resolved_by')->nullable()->after('resolved_at')->constrained('users')->nullOnDelete();
        });

        Schema::table('me_audit_logs', function (Blueprint $table) {
            $table->string('entity_type')->nullable()->after('table_name');
            $table->unsignedBigInteger('entity_id')->nullable()->after('entity_type');
            $table->json('before')->nullable()->after('action');
            $table->json('after')->nullable()->after('before');
        });

        Schema::table('me_surveys', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('id')->constrained('me_projects')->nullOnDelete();
            $table->foreignId('reporting_period_id')->nullable()->after('type')->constrained('me_reporting_periods')->nullOnDelete();
            $table->string('version')->nullable()->after('title');
        });

        Schema::table('me_survey_questions', function (Blueprint $table) {
            $table->foreignId('maps_to_indicator_id')->nullable()->after('survey_id')->constrained('me_indicators')->nullOnDelete();
            $table->json('validation_rules')->nullable()->after('options');
            $table->integer('sort_order')->default(0)->after('validation_rules');
        });

        Schema::table('me_survey_responses', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('survey_id')->constrained('me_projects')->nullOnDelete();
            $table->foreignId('reporting_period_id')->nullable()->after('project_id')->constrained('me_reporting_periods')->nullOnDelete();
            $table->foreignId('submitted_by')->nullable()->after('reporting_period_id')->constrained('users')->nullOnDelete();
            $table->enum('source', ['web', 'mobile', 'import', 'api'])->default('web')->after('submitted_by');
        });

        Schema::table('me_survey_answers', function (Blueprint $table) {
            $table->json('answer_value')->nullable()->after('question_id');
        });

        Schema::table('me_beneficiary_feedback', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('id')->constrained('me_projects')->nullOnDelete();
            $table->foreignId('reporting_period_id')->nullable()->after('project_id')->constrained('me_reporting_periods')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->after('reporting_period_id')->constrained('me_locations')->nullOnDelete();
            $table->foreignId('gender_option_id')->nullable()->after('location_id')->constrained('me_disaggregation_options')->nullOnDelete();
            $table->foreignId('age_group_option_id')->nullable()->after('gender_option_id')->constrained('me_disaggregation_options')->nullOnDelete();
            $table->foreignId('disability_option_id')->nullable()->after('age_group_option_id')->constrained('me_disaggregation_options')->nullOnDelete();
            $table->unsignedTinyInteger('rating')->nullable()->after('sentiment');
            $table->string('channel')->nullable()->after('rating');
        });

        Schema::create('me_validation_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indicator_id')->constrained('me_indicators')->cascadeOnDelete();
            $table->json('rules_json');
            $table->string('message')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('me_indicator_analytics_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indicator_id')->constrained('me_indicators')->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('me_projects')->nullOnDelete();
            $table->foreignId('reporting_period_id')->nullable()->constrained('me_reporting_periods')->nullOnDelete();
            $table->decimal('progress_percent', 7, 2)->nullable();
            $table->string('trend_direction')->nullable();
            $table->decimal('moving_avg', 14, 2)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['indicator_id', 'reporting_period_id'], 'me_indicator_snapshot_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('me_indicator_analytics_snapshots');
        Schema::dropIfExists('me_validation_rules');
        Schema::dropIfExists('me_indicator_target_disaggregations');

        Schema::table('me_beneficiary_feedback', function (Blueprint $table) {
            $table->dropConstrainedForeignId('disability_option_id');
            $table->dropConstrainedForeignId('age_group_option_id');
            $table->dropConstrainedForeignId('gender_option_id');
            $table->dropConstrainedForeignId('location_id');
            $table->dropConstrainedForeignId('reporting_period_id');
            $table->dropConstrainedForeignId('project_id');
            $table->dropColumn(['rating', 'channel']);
        });

        Schema::table('me_survey_answers', function (Blueprint $table) {
            $table->dropColumn('answer_value');
        });

        Schema::table('me_survey_responses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('submitted_by');
            $table->dropConstrainedForeignId('reporting_period_id');
            $table->dropConstrainedForeignId('project_id');
            $table->dropColumn('source');
        });

        Schema::table('me_survey_questions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('maps_to_indicator_id');
            $table->dropColumn(['validation_rules', 'sort_order']);
        });

        Schema::table('me_surveys', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reporting_period_id');
            $table->dropConstrainedForeignId('project_id');
            $table->dropColumn('version');
        });

        Schema::table('me_audit_logs', function (Blueprint $table) {
            $table->dropColumn(['entity_type', 'entity_id', 'before', 'after']);
        });

        Schema::table('me_alerts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('resolved_by');
            $table->dropConstrainedForeignId('reporting_period_id');
            $table->dropConstrainedForeignId('project_id');
            $table->dropColumn('status');
        });

        Schema::table('me_alert_rules', function (Blueprint $table) {
            $table->dropConstrainedForeignId('indicator_id');
            $table->dropColumn(['rule_type', 'threshold', 'severity']);
        });

        Schema::table('me_indicator_reports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('entered_by');
            $table->dropConstrainedForeignId('reporting_period_id');
            $table->dropColumn(['source', 'entered_at', 'comment', 'actual_text']);
        });

        Schema::table('me_indicator_targets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reporting_period_id');
            $table->dropColumn(['target_min', 'target_max', 'notes']);
        });

        Schema::table('me_indicators', function (Blueprint $table) {
            $table->dropIndex('me_indicators_project_active_idx');
            $table->dropConstrainedForeignId('project_id');
            $table->dropColumn(['name_local', 'direction', 'data_type']);
        });

        Schema::dropIfExists('me_locations');
        Schema::dropIfExists('me_reporting_periods');
        Schema::dropIfExists('me_projects');
    }
};


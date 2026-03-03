<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('me_projects')) {
            Schema::create('me_projects', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('project_code')->unique();
                $table->text('description')->nullable();
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('me_reporting_periods')) {
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
        }

        if (! Schema::hasTable('me_locations')) {
            Schema::create('me_locations', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('type')->nullable();
                $table->foreignId('parent_id')->nullable()->constrained('me_locations')->nullOnDelete();
                $table->decimal('lat', 10, 7)->nullable();
                $table->decimal('lng', 10, 7)->nullable();
                $table->timestamps();
            });
        }

        Schema::table('me_indicators', function (Blueprint $table) {
            if (! Schema::hasColumn('me_indicators', 'project_id')) {
                $table->foreignId('project_id')->nullable()->after('id')->constrained('me_projects')->nullOnDelete();
            }
            if (! Schema::hasColumn('me_indicators', 'name_local')) {
                $table->string('name_local')->nullable()->after('name');
            }
            if (! Schema::hasColumn('me_indicators', 'direction')) {
                $table->enum('direction', ['higher_is_better', 'lower_is_better'])->default('higher_is_better')->after('unit');
            }
            if (! Schema::hasColumn('me_indicators', 'data_type')) {
                $table->enum('data_type', ['number', 'integer', 'percent', 'currency', 'text'])->default('number')->after('direction');
            }
            if (! Schema::hasIndex('me_indicators', 'me_indicators_project_active_idx')) {
                $table->index(['project_id', 'is_active'], 'me_indicators_project_active_idx');
            }
        });

        Schema::table('me_indicator_targets', function (Blueprint $table) {
            if (! Schema::hasColumn('me_indicator_targets', 'reporting_period_id')) {
                $table->foreignId('reporting_period_id')->nullable()->after('indicator_id')->constrained('me_reporting_periods')->nullOnDelete();
            }
            if (! Schema::hasColumn('me_indicator_targets', 'target_min')) {
                $table->decimal('target_min', 14, 2)->nullable()->after('target_value');
            }
            if (! Schema::hasColumn('me_indicator_targets', 'target_max')) {
                $table->decimal('target_max', 14, 2)->nullable()->after('target_min');
            }
            if (! Schema::hasColumn('me_indicator_targets', 'notes')) {
                $table->text('notes')->nullable()->after('target_max');
            }
        });

        Schema::table('me_indicator_reports', function (Blueprint $table) {
            if (! Schema::hasColumn('me_indicator_reports', 'reporting_period_id')) {
                $table->foreignId('reporting_period_id')->nullable()->after('indicator_id')->constrained('me_reporting_periods')->nullOnDelete();
            }
            if (! Schema::hasColumn('me_indicator_reports', 'source')) {
                $table->enum('source', ['manual', 'import', 'survey', 'api'])->default('manual')->after('actual_value');
            }
            if (! Schema::hasColumn('me_indicator_reports', 'entered_by')) {
                $table->foreignId('entered_by')->nullable()->after('source')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('me_indicator_reports', 'entered_at')) {
                $table->timestamp('entered_at')->nullable()->after('entered_by');
            }
            if (! Schema::hasColumn('me_indicator_reports', 'comment')) {
                $table->text('comment')->nullable()->after('notes');
            }
            if (! Schema::hasColumn('me_indicator_reports', 'actual_text')) {
                $table->text('actual_text')->nullable()->after('actual_value');
            }
        });

        if (! Schema::hasTable('me_indicator_target_disaggregations')) {
            Schema::create('me_indicator_target_disaggregations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('target_id')->constrained('me_indicator_targets')->cascadeOnDelete();
                $table->unsignedBigInteger('disaggregation_option_id');
                $table->foreign('disaggregation_option_id', 'me_target_disagg_option_fk')
                    ->references('id')
                    ->on('me_disaggregation_options')
                    ->cascadeOnDelete();
                $table->decimal('value', 14, 2);
                $table->timestamps();

                $table->unique(['target_id', 'disaggregation_option_id'], 'me_target_disagg_unique');
            });
        }

        Schema::table('me_alert_rules', function (Blueprint $table) {
            if (! Schema::hasColumn('me_alert_rules', 'indicator_id')) {
                $table->foreignId('indicator_id')->nullable()->after('id')->constrained('me_indicators')->cascadeOnDelete();
            }
            if (! Schema::hasColumn('me_alert_rules', 'rule_type')) {
                $table->enum('rule_type', ['below_percent_of_target', 'below_target', 'above_target', 'outside_range'])
                    ->nullable()
                    ->after('condition');
            }
            if (! Schema::hasColumn('me_alert_rules', 'threshold')) {
                $table->decimal('threshold', 10, 2)->nullable()->after('rule_type');
            }
            if (! Schema::hasColumn('me_alert_rules', 'severity')) {
                $table->enum('severity', ['info', 'warning', 'critical'])->default('warning')->after('threshold');
            }
        });

        Schema::table('me_alerts', function (Blueprint $table) {
            if (! Schema::hasColumn('me_alerts', 'project_id')) {
                $table->foreignId('project_id')->nullable()->after('id')->constrained('me_projects')->nullOnDelete();
            }
            if (! Schema::hasColumn('me_alerts', 'reporting_period_id')) {
                $table->foreignId('reporting_period_id')->nullable()->after('report_id')->constrained('me_reporting_periods')->nullOnDelete();
            }
            if (! Schema::hasColumn('me_alerts', 'status')) {
                $table->enum('status', ['open', 'acknowledged', 'resolved'])->default('open')->after('severity');
            }
            if (! Schema::hasColumn('me_alerts', 'resolved_by')) {
                $table->foreignId('resolved_by')->nullable()->after('resolved_at')->constrained('users')->nullOnDelete();
            }
        });

        Schema::table('me_audit_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('me_audit_logs', 'entity_type')) {
                $table->string('entity_type')->nullable()->after('table_name');
            }
            if (! Schema::hasColumn('me_audit_logs', 'entity_id')) {
                $table->unsignedBigInteger('entity_id')->nullable()->after('entity_type');
            }
            if (! Schema::hasColumn('me_audit_logs', 'before')) {
                $table->json('before')->nullable()->after('action');
            }
            if (! Schema::hasColumn('me_audit_logs', 'after')) {
                $table->json('after')->nullable()->after('before');
            }
        });

        Schema::table('me_surveys', function (Blueprint $table) {
            if (! Schema::hasColumn('me_surveys', 'project_id')) {
                $table->foreignId('project_id')->nullable()->after('id')->constrained('me_projects')->nullOnDelete();
            }
            if (! Schema::hasColumn('me_surveys', 'reporting_period_id')) {
                $table->foreignId('reporting_period_id')->nullable()->after('type')->constrained('me_reporting_periods')->nullOnDelete();
            }
            if (! Schema::hasColumn('me_surveys', 'version')) {
                $table->string('version')->nullable()->after('title');
            }
        });

        Schema::table('me_survey_questions', function (Blueprint $table) {
            if (! Schema::hasColumn('me_survey_questions', 'maps_to_indicator_id')) {
                $table->foreignId('maps_to_indicator_id')->nullable()->after('survey_id')->constrained('me_indicators')->nullOnDelete();
            }
            if (! Schema::hasColumn('me_survey_questions', 'validation_rules')) {
                $table->json('validation_rules')->nullable()->after('options');
            }
            if (! Schema::hasColumn('me_survey_questions', 'sort_order')) {
                $table->integer('sort_order')->default(0)->after('validation_rules');
            }
        });

        Schema::table('me_survey_responses', function (Blueprint $table) {
            if (! Schema::hasColumn('me_survey_responses', 'project_id')) {
                $table->foreignId('project_id')->nullable()->after('survey_id')->constrained('me_projects')->nullOnDelete();
            }
            if (! Schema::hasColumn('me_survey_responses', 'reporting_period_id')) {
                $table->foreignId('reporting_period_id')->nullable()->after('project_id')->constrained('me_reporting_periods')->nullOnDelete();
            }
            if (! Schema::hasColumn('me_survey_responses', 'submitted_by')) {
                $table->foreignId('submitted_by')->nullable()->after('reporting_period_id')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('me_survey_responses', 'source')) {
                $table->enum('source', ['web', 'mobile', 'import', 'api'])->default('web')->after('submitted_by');
            }
        });

        Schema::table('me_survey_answers', function (Blueprint $table) {
            if (! Schema::hasColumn('me_survey_answers', 'answer_value')) {
                $table->json('answer_value')->nullable()->after('question_id');
            }
        });

        Schema::table('me_beneficiary_feedback', function (Blueprint $table) {
            if (! Schema::hasColumn('me_beneficiary_feedback', 'project_id')) {
                $table->foreignId('project_id')->nullable()->after('id')->constrained('me_projects')->nullOnDelete();
            }
            if (! Schema::hasColumn('me_beneficiary_feedback', 'reporting_period_id')) {
                $table->foreignId('reporting_period_id')->nullable()->after('project_id')->constrained('me_reporting_periods')->nullOnDelete();
            }
            if (! Schema::hasColumn('me_beneficiary_feedback', 'location_id')) {
                $table->foreignId('location_id')->nullable()->after('reporting_period_id')->constrained('me_locations')->nullOnDelete();
            }
            if (! Schema::hasColumn('me_beneficiary_feedback', 'gender_option_id')) {
                $table->foreignId('gender_option_id')->nullable()->after('location_id')->constrained('me_disaggregation_options')->nullOnDelete();
            }
            if (! Schema::hasColumn('me_beneficiary_feedback', 'age_group_option_id')) {
                $table->foreignId('age_group_option_id')->nullable()->after('gender_option_id')->constrained('me_disaggregation_options')->nullOnDelete();
            }
            if (! Schema::hasColumn('me_beneficiary_feedback', 'disability_option_id')) {
                $table->foreignId('disability_option_id')->nullable()->after('age_group_option_id')->constrained('me_disaggregation_options')->nullOnDelete();
            }
            if (! Schema::hasColumn('me_beneficiary_feedback', 'rating')) {
                $table->unsignedTinyInteger('rating')->nullable()->after('sentiment');
            }
            if (! Schema::hasColumn('me_beneficiary_feedback', 'channel')) {
                $table->string('channel')->nullable()->after('rating');
            }
        });

        if (! Schema::hasTable('me_validation_rules')) {
            Schema::create('me_validation_rules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('indicator_id')->constrained('me_indicators')->cascadeOnDelete();
                $table->json('rules_json');
                $table->string('message')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('me_indicator_analytics_snapshots')) {
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


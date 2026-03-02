<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('me_alert_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->enum('condition', ['below_percent', 'below_variance']);
            $table->decimal('warning_threshold', 5, 2)->nullable();
            $table->decimal('critical_threshold', 5, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('me_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indicator_id')->constrained('me_indicators')->cascadeOnDelete();
            $table->foreignId('report_id')->nullable()->constrained('me_indicator_reports')->nullOnDelete();
            $table->enum('severity', ['info', 'warning', 'critical']);
            $table->string('message');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['severity', 'resolved_at'], 'me_alerts_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('me_alerts');
        Schema::dropIfExists('me_alert_rules');
    }
};

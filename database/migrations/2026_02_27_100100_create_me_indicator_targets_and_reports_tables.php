<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('me_indicator_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indicator_id')->constrained('me_indicators')->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('target_value', 14, 2);
            $table->string('scope_location')->nullable();
            $table->string('scope_project')->nullable();
            $table->timestamps();

            $table->index(['indicator_id', 'period_start', 'period_end'], 'me_targets_period_idx');
            $table->index(['scope_location', 'scope_project'], 'me_targets_scope_idx');
        });

        Schema::create('me_indicator_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indicator_id')->constrained('me_indicators')->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('actual_value', 14, 2);
            $table->string('scope_location')->nullable();
            $table->string('scope_project')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['indicator_id', 'period_start', 'period_end'], 'me_reports_period_idx');
            $table->index(['scope_location', 'scope_project'], 'me_reports_scope_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('me_indicator_reports');
        Schema::dropIfExists('me_indicator_targets');
    }
};

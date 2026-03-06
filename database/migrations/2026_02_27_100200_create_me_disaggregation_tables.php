<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('me_disaggregation_categories', function (Blueprint $table) {
            $table->id();
            $table->enum('key', ['gender', 'age', 'location', 'disability', 'custom']);
            $table->string('name');
            $table->json('rules')->nullable();
            $table->timestamps();

            $table->index('key');
        });

        Schema::create('me_disaggregation_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('me_disaggregation_categories')->cascadeOnDelete();
            $table->string('value');
            $table->string('label');
            $table->integer('sort_order')->default(0);

            $table->unique(['category_id', 'value'], 'me_disaggregation_options_unique');
        });

        Schema::create('me_indicator_disaggregation', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indicator_id')->constrained('me_indicators')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('me_disaggregation_categories')->cascadeOnDelete();

            $table->unique(['indicator_id', 'category_id'], 'me_indicator_disaggregation_unique');
        });

        Schema::create('me_report_disaggregation_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('me_indicator_reports')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('me_disaggregation_categories')->cascadeOnDelete();
            $table->foreignId('option_id')->constrained('me_disaggregation_options')->cascadeOnDelete();
            $table->decimal('value', 14, 2);

            $table->unique(['report_id', 'category_id', 'option_id'], 'me_report_disagg_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('me_report_disaggregation_values');
        Schema::dropIfExists('me_indicator_disaggregation');
        Schema::dropIfExists('me_disaggregation_options');
        Schema::dropIfExists('me_disaggregation_categories');
    }
};

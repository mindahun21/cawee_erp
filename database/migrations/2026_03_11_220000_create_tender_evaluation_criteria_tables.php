<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tender-level evaluation criteria (name + weight)
        Schema::create('procurement_tender_evaluation_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tender_id')
                  ->constrained('procurement_tenders')
                  ->cascadeOnDelete();
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->decimal('weight', 5, 2)->default(0); // % — must sum to 100 per tender
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Per-bid scores against each criterion
        Schema::create('procurement_bid_criterion_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bid_id')
                  ->constrained('procurement_bids')
                  ->cascadeOnDelete();
            $table->foreignId('criterion_id')
                  ->constrained('procurement_tender_evaluation_criteria')
                  ->cascadeOnDelete();
            $table->decimal('score', 5, 2)->default(0); // 0–100
            $table->text('notes')->nullable();
            $table->foreignId('scored_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('scored_at')->nullable();
            $table->timestamps();

            $table->unique(['bid_id', 'criterion_id']); // one row per bid × criterion
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procurement_bid_criterion_scores');
        Schema::dropIfExists('procurement_tender_evaluation_criteria');
    }
};

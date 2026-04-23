<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment_campaign_skill', function (Blueprint $table) {
            $table->id();

            $table->foreignId('campaign_id')
                  ->constrained('recruitment_campaigns')
                  ->cascadeOnDelete();
            $table->foreignId('recruitment_skill_id')
                  ->constrained('recruitment_skills')
                  ->restrictOnDelete();

            $table->boolean('is_required')->default(true);
            $table->unsignedTinyInteger('min_proficiency')->nullable();

            $table->timestamps();

            $table->unique(
                ['campaign_id', 'recruitment_skill_id'],
                'campaign_skill_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment_campaign_skill');
    }
};

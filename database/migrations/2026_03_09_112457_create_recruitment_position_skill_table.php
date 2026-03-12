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
        Schema::create('recruitment_position_skill', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recruitment_position_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('recruitment_skill_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recruitment_position_skill');
    }
};

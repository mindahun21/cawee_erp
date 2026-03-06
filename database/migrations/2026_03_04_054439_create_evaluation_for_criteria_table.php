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
        Schema::create('evaluation_form_criterias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('criteria_group_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('evaluation_criteria_id')
                ->constrained('evaluation_criterias')
                ->cascadeOnDelete();

            $table->decimal('proportion', 5, 2);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluation_form_criterias');
    }
};

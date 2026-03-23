<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment_evaluation_criterias', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('criteria_type'); // group_criteria | evaluation_criteria
            $table->string('score_1_desc')->nullable();
            $table->string('score_2_desc')->nullable();
            $table->string('score_3_desc')->nullable();
            $table->string('score_4_desc')->nullable();
            $table->string('score_5_desc')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment_evaluation_criterias');
    }
};

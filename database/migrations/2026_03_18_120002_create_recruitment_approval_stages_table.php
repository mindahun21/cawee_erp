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
        Schema::create('recruitment_approval_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('recruitment_approval_workflows')->cascadeOnDelete();
            $table->string('stage_name');
            $table->unsignedTinyInteger('stage_order');
            $table->string('required_role');
            $table->boolean('can_reject')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recruitment_approval_stages');
    }
};

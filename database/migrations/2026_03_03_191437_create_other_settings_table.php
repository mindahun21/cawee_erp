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
        Schema::create('other_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('show_recruitment_plan')->default(false);
            $table->boolean('display_quantity_on_portal')->default(false);
            $table->boolean('send_welcome_email')->default(false);
            $table->string('candidate_code_prefix')->nullable();
            $table->unsignedBigInteger('next_candidate_code_number')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('other_settings');
    }
};

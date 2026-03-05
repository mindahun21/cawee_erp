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
        Schema::create('onboarding_processes', function (Blueprint $table) {
            $table->id();
            $table->integer('order');
            $table->foreignId('send_to_id')->constrained('users')->cascadeOnDelete();
            $table->text('subject');
            $table->text('content')->nullable();
            $table->foreignId('added_by')
                ->nullable()
                ->constrained('users');
            $table->string('attachment')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('onboarding_processes');
    }
};

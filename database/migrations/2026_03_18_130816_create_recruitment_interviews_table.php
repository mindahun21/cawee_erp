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
        Schema::create('recruitment_interviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('recruitment_applications')->cascadeOnDelete();
            $table->string('type');             // online | physical | panel | technical
            $table->unsignedTinyInteger('round')->default(1);
            $table->dateTime('scheduled_at');
            $table->unsignedSmallInteger('duration_minutes')->default(60);
            $table->string('location')->nullable();       // Room / Zoom link
            $table->string('status')->default('scheduled'); // scheduled | completed | cancelled | no_show
            $table->foreignId('scheduled_by')->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['application_id', 'status']);
            $table->index('scheduled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recruitment_interviews');
    }
};

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
        Schema::create('event_volunteers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('donor_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('phone', 20)->nullable();
            $table->string('role', 100);
            $table->text('tasks')->nullable();
            $table->decimal('hours_committed', 5, 2)->default(0.00);
            $table->decimal('hours_completed', 5, 2)->default(0.00);
            $table->dateTime('check_in_time')->nullable();
            $table->dateTime('check_out_time')->nullable();
            $table->enum('status', ['registered', 'confirmed', 'attended', 'cancelled', 'no_show'])->default('registered');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->unique(['campaign_event_id', 'email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_volunteers');
    }
};

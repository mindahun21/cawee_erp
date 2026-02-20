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
        Schema::create('event_attendees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('donor_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name')->nullable(); // For non-donor attendees
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->enum('status', ['confirmed', 'pending', 'declined'])->default('pending');
            $table->integer('guests')->unsigned()->default(0);
            $table->integer('tickets_purchased')->unsigned()->default(0);
            $table->decimal('amount_paid', 10, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->dateTime('check_in_time')->nullable();
            $table->dateTime('check_out_time')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_attendees');
    }
};

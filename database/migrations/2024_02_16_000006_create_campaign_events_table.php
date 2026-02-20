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
        Schema::create('campaign_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->string('event_name');
            $table->enum('event_type', ['fundraiser', 'meeting', 'volunteer', 'awareness', 'other'])->default('fundraiser');
            $table->enum('status', ['planned', 'confirmed', 'ongoing', 'completed', 'cancelled'])->default('planned');
            $table->dateTime('event_date');
            $table->dateTime('end_date')->nullable();
            $table->string('venue')->nullable();
            $table->text('venue_address')->nullable();
            $table->text('description')->nullable();
            $table->integer('expected_attendees')->unsigned()->nullable()->default(0);
            $table->integer('max_capacity')->unsigned()->nullable();
            $table->boolean('rsvp_required')->default(false);
            $table->dateTime('rsvp_deadline')->nullable();
            $table->decimal('ticket_price', 10, 2)->default(0.00);
            $table->integer('tickets_sold')->unsigned()->default(0);
            $table->decimal('budget', 10, 2)->default(0.00);
            $table->decimal('actual_cost', 10, 2)->default(0.00);
            $table->decimal('funds_raised', 10, 2)->default(0.00);
            $table->boolean('funds_to_campaign')->default(true);
            $table->string('organizer_name', 100)->nullable();
            $table->string('organizer_email')->nullable();
            $table->string('organizer_phone', 20)->nullable();
            $table->string('registration_link', 500)->nullable();
            $table->string('social_media_link', 500)->nullable();
            $table->integer('volunteers_needed')->unsigned()->default(0);
            $table->integer('volunteers_registered')->unsigned()->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('event_type');
            $table->index(['campaign_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_events');
    }
};

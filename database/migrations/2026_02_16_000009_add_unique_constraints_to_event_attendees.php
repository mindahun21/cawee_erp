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
        Schema::table('event_attendees', function (Blueprint $table) {
            // Unique constraint for donors per event
            $table->unique(['campaign_event_id', 'donor_id'], 'event_attendee_donor_unique');
            
            // Unique constraint for email per event (for non-donors or catching duplicates)
            // Note: SQLite allows multiple NULLs in unique constraints, so this works fine for those with donor_id but no email (though email is string)
            $table->unique(['campaign_event_id', 'email'], 'event_attendee_email_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_attendees', function (Blueprint $table) {
            $table->dropUnique('event_attendee_donor_unique');
            $table->dropUnique('event_attendee_email_unique');
        });
    }
};

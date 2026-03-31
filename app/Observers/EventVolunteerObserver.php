<?php

namespace App\Observers;

use App\Models\EventVolunteer;

class EventVolunteerObserver
{
    /**
     * Handle the EventVolunteer "created" event.
     */
    public function created(EventVolunteer $eventVolunteer): void
    {
        $eventVolunteer->event?->syncCounts();
    }

    /**
     * Handle the EventVolunteer "updated" event.
     */
    public function updated(EventVolunteer $eventVolunteer): void
    {
        $eventVolunteer->event?->syncCounts();
    }

    /**
     * Handle the EventVolunteer "deleted" event.
     */
    public function deleted(EventVolunteer $eventVolunteer): void
    {
        $eventVolunteer->event?->syncCounts();
    }

    /**
     * Handle the EventVolunteer "restored" event.
     */
    public function restored(EventVolunteer $eventVolunteer): void
    {
        $eventVolunteer->event?->syncCounts();
    }

    /**
     * Handle the EventVolunteer "force deleted" event.
     */
    public function forceDeleted(EventVolunteer $eventVolunteer): void
    {
        $eventVolunteer->event?->syncCounts();
    }
}

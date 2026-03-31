<?php

namespace App\Observers;

use App\Mail\EventInvitation;
use App\Models\EventAttendee;
use Illuminate\Support\Facades\Mail;

class EventAttendeeObserver
{
    /**
     * Handle the EventAttendee "created" event.
     */
    public function created(EventAttendee $eventAttendee): void
    {
        $recipient = $eventAttendee->email ?? $eventAttendee->donor?->email;

        if ($recipient) {
            Mail::to($recipient)->send(new EventInvitation($eventAttendee, $eventAttendee->event));
        }

        $eventAttendee->event?->syncCounts();
    }

    /**
     * Handle the EventAttendee "updated" event.
     */
    public function updated(EventAttendee $eventAttendee): void
    {
        $eventAttendee->event?->syncCounts();
    }

    /**
     * Handle the EventAttendee "deleted" event.
     */
    public function deleted(EventAttendee $eventAttendee): void
    {
        $eventAttendee->event?->syncCounts();
    }

    /**
     * Handle the EventAttendee "restored" event.
     */
    public function restored(EventAttendee $eventAttendee): void
    {
        $eventAttendee->event?->syncCounts();
    }

    /**
     * Handle the EventAttendee "force deleted" event.
     */
    public function forceDeleted(EventAttendee $eventAttendee): void
    {
        $eventAttendee->event?->syncCounts();
    }
}

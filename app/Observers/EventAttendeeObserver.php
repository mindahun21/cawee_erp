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
    }
}

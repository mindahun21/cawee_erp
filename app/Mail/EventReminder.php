<?php

namespace App\Mail;

use App\Models\CampaignEvent;
use App\Models\EventAttendee;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EventReminder extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public EventAttendee $attendee,
        public CampaignEvent $event
    ) {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reminder: ' . $this->event->event_name,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.reminder',
            with: [
                'attendeeName' => $this->attendee->name ?? $this->attendee->donor?->full_name ?? 'Guest',
                'eventName' => $this->event->event_name,
                'eventDate' => $this->event->event_date,
                'eventVenue' => $this->event->venue,
                'eventAddress' => $this->event->venue_address,
                'eventDescription' => $this->event->description,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}

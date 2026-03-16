<?php

namespace App\Mail;

use App\Models\Procurement\Bid;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BidAwardedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Bid $bid) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Congratulations — Your Bid Has Been Awarded | {$this->bid->tender->tender_number}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.bid-awarded',
            with: ['bid' => $this->bid],
        );
    }
}

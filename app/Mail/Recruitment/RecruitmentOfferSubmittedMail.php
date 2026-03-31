<?php

namespace App\Mail\Recruitment;

use App\Models\Recruitment\RecruitmentOffer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RecruitmentOfferSubmittedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public RecruitmentOffer $offer,
        public string $viewUrl
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Employment Offer Submitted for Approval',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.recruitment.offer-submitted',
        );
    }
}

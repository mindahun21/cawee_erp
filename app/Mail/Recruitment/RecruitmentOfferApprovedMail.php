<?php

namespace App\Mail\Recruitment;

use App\Models\Recruitment\RecruitmentOffer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RecruitmentOfferApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public RecruitmentOffer $offer,
        public string $viewUrl
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Employment Offer Approved',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.recruitment.offer-approved',
        );
    }
}

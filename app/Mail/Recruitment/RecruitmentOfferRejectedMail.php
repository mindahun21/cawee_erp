<?php

namespace App\Mail\Recruitment;

use App\Models\Recruitment\RecruitmentOffer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RecruitmentOfferRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public RecruitmentOffer $offer,
        public string $viewUrl
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Employment Offer Returned for Review',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.recruitment.offer-rejected',
        );
    }
}

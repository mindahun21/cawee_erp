<?php

namespace App\Mail\Recruitment;

use App\Models\Recruitment\RecruitmentOffer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RecruitmentOfferCandidateMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public RecruitmentOffer $offer,
        public string $portalOfferUrl,
        public string $loginUrl
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Congratulations! You Have Received a Job Offer',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.recruitment.offer-candidate',
        );
    }
}

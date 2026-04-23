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

    public function attachments(): array
    {
        $attachments = [];
        if ($this->offer->offer_letter_path && \Illuminate\Support\Facades\Storage::disk('private')->exists($this->offer->offer_letter_path)) {
            $attachments[] = \Illuminate\Mail\Mailables\Attachment::fromStorageDisk(
                'private', 
                $this->offer->offer_letter_path
            )->as('Employment_Offer_Letter.pdf');
        }
        return $attachments;
    }
}

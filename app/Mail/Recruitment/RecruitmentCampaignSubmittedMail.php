<?php

namespace App\Mail\Recruitment;

use App\Models\Recruitment\RecruitmentCampaign;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RecruitmentCampaignSubmittedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public RecruitmentCampaign $campaign,
        public string $viewUrl
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Recruitment Campaign Submitted for Review',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.recruitment.campaign-submitted',
        );
    }
}

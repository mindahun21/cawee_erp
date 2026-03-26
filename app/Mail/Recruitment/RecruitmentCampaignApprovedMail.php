<?php

namespace App\Mail\Recruitment;

use App\Models\Recruitment\RecruitmentCampaign;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RecruitmentCampaignApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public RecruitmentCampaign $campaign,
        public string $viewUrl
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Recruitment Campaign Approved',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.recruitment.campaign-approved',
        );
    }
}

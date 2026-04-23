<?php

namespace App\Mail\Recruitment;

use App\Models\Recruitment\RecruitmentPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RecruitmentPlanApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public RecruitmentPlan $plan,
        public string $viewUrl
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Recruitment Plan Approved',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.recruitment.plan-approved',
        );
    }
}

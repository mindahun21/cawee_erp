<?php

namespace App\Mail\Recruitment;

use App\Models\Recruitment\RecruitmentApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CandidateRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public RecruitmentApplication $application,
        public ?string $reason = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Update regarding your application for ' . ($this->application->campaign->jobPosition->title ?? $this->application->campaign->title),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.recruitment.candidate-rejected',
        );
    }
}

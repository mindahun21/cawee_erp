<?php

namespace App\Mail\Recruitment;

use App\Models\Recruitment\RecruitmentInterviewSchedule;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RecruitmentInterviewScheduleSubmittedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public RecruitmentInterviewSchedule $schedule,
        public string $viewUrl
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Interview Schedule Submitted for Review',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.recruitment.schedule-submitted',
        );
    }
}

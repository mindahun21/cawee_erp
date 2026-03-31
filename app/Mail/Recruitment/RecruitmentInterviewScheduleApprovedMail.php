<?php

namespace App\Mail\Recruitment;

use App\Models\Recruitment\RecruitmentInterviewSchedule;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RecruitmentInterviewScheduleApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public RecruitmentInterviewSchedule $schedule,
        public string $viewUrl
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Interview Schedule Approved',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.recruitment.schedule-approved',
        );
    }
}

<?php

namespace App\Notifications\Recruitment;

use App\Models\Recruitment\RecruitmentInterviewSchedule;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InterviewAssignmentNotification extends Notification
{
    use Queueable;

    public function __construct(
        public RecruitmentInterviewSchedule $schedule,
        public string $role,
        public string $viewUrl
    ) {}

    public function via(object $notifiable): array
    {
        // In-db notification is already sent via Filament in the Observer
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $campaignTitle = $this->schedule->campaign->title ?? 'Position';

        return (new MailMessage)
            ->subject('Interview Assignment: ' . $campaignTitle)
            ->view('emails.recruitment.interview-assignment', [
                'schedule' => $this->schedule,
                'role' => $this->role,
                'viewUrl' => $this->viewUrl,
                'campaignTitle' => $campaignTitle,
                'notifiable' => $notifiable,
            ]);
    }
}

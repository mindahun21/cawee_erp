<?php

namespace App\Notifications\Recruitment;

use App\Models\Recruitment\RecruitmentCandidate;
use App\Models\Recruitment\RecruitmentInterviewSchedule;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InterviewInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(
        public RecruitmentInterviewSchedule $schedule,
        public RecruitmentCandidate $candidate,
        public string $slotStart,
        public string $slotEnd
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $campaignTitle = $this->schedule->campaign->title ?? 'Position';

        return (new MailMessage)
            ->subject('Interview Invitation: ' . $campaignTitle)
            ->view('emails.recruitment.interview-invitation', [
                'schedule' => $this->schedule,
                'candidate' => $this->candidate,
                'slotStart' => $this->slotStart,
                'slotEnd' => $this->slotEnd,
                'campaignTitle' => $campaignTitle,
            ]);
    }
}

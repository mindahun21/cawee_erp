<?php

namespace App\Observers\Recruitment;

use App\Models\Recruitment\RecruitmentCandidateEvaluation;
use App\Models\Recruitment\RecruitmentApplication;
use App\Services\Recruitment\RecruitmentApplicationService;

class RecruitmentCandidateEvaluationObserver
{
    public function created(RecruitmentCandidateEvaluation $evaluation): void
    {
        $this->transitionToInterviewed($evaluation);
    }

    public function updated(RecruitmentCandidateEvaluation $evaluation): void
    {
        $this->transitionToInterviewed($evaluation);
    }

    protected function transitionToInterviewed(RecruitmentCandidateEvaluation $evaluation): void
    {
        $schedule = $evaluation->schedule;
        if (!$schedule) return;

        $application = RecruitmentApplication::query()
            ->where('campaign_id', $schedule->campaign_id)
            ->where('candidate_id', $evaluation->candidate_id)
            ->where('status', RecruitmentApplication::STATUS_INTERVIEW_SCHEDULED)
            ->first();

        if ($application) {
            app(RecruitmentApplicationService::class)
                ->transition(
                    $application, 
                    RecruitmentApplication::STATUS_INTERVIEWED, 
                    auth()->user(), 
                    'Auto-transitioned via candidate evaluation'
                );
        }
    }
}

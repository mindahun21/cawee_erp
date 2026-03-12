<?php

namespace App\Filament\Widgets;

use App\Models\InterviewCandidate;
use App\Models\RecruitmentCampaign;
use App\Models\RecruitmentPosition;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RecruitmentStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            // Card::make('Total Campaigns', RecruitmentCampaign::count()),
            // Card::make('Campaigns In Progress', RecruitmentCampaign::where('status', 'in_progress')->count()),
            // Card::make('Planned Campaigns', RecruitmentCampaign::where('status', 'planned')->count()),
            // Card::make('Completed Campaigns', RecruitmentCampaign::where('status', 'completed')->count()),
            // Card::make('Candidates Need to Recruit', RecruitmentPosition::where('filled', false)->count()),
            // Card::make('Candidates Recruited', InterviewCandidate::count()),
            // Card::make('Upcoming Interviews', RecruitmentInterview::where('interview_date', '>', now())->count()),
            // Card::make('Campaigns Running', RecruitmentCampaign::where('status', 'in_progress')->count()),
        ];
    }
}

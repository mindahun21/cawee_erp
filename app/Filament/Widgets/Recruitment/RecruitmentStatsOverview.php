<?php

namespace App\Filament\Widgets\Recruitment;

use App\Traits\BelongsToModuleWidget;

use App\Models\Recruitment\RecruitmentCandidate;
use App\Models\Recruitment\RecruitmentApplication;
use App\Models\Recruitment\RecruitmentCampaign;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RecruitmentStatsOverview extends BaseWidget
{
    use BelongsToModuleWidget;

    /**
     * @var int|null
     */
    protected static ?int $sort = 1;

    protected string $view = 'filament-widgets::stats-overview-widget';

    protected function getStats(): array
    {
        return [
            Stat::make('Active Campaigns', RecruitmentCampaign::where('status', RecruitmentCampaign::STATUS_ACTIVE)->count())
                ->description('Total active public campaigns')
                ->descriptionIcon('heroicon-m-briefcase')
                ->color('primary'),

            Stat::make('Total Candidates', RecruitmentCandidate::count())
                ->description('Registered candidates in the system')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),

            Stat::make('Total Applications', RecruitmentApplication::count())
                ->description('Applications submitted across all campaigns')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info'),
        ];
    }
}

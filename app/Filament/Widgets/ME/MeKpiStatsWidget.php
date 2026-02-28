<?php

namespace App\Filament\Widgets\ME;

use App\Filament\Widgets\ME\Concerns\InteractsWithMeFilters;
use App\Services\ME\DashboardService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MeKpiStatsWidget extends BaseWidget
{
    use InteractsWithMeFilters;
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $kpis = app(DashboardService::class)->kpis($this->getMeFilters());

        return [
            Stat::make('Total Indicators', number_format((int) $kpis['total_indicators']))
                ->color('primary'),
            Stat::make('Reported This Period', number_format((int) $kpis['reported_this_period']))
                ->color('info'),
            Stat::make('On Track', number_format((int) $kpis['on_track']))
                ->color('success'),
            Stat::make('Needs Attention', number_format((int) $kpis['needs_attention']))
                ->color('warning'),
            Stat::make('Off Track', number_format((int) $kpis['off_track']))
                ->color('danger'),
            Stat::make('Coverage Rate', number_format((float) $kpis['coverage_rate'], 2) . '%')
                ->description('reported / total indicators')
                ->color('gray'),
        ];
    }
}

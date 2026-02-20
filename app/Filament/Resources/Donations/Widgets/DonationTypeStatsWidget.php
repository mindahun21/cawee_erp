<?php

namespace App\Filament\Resources\Donations\Widgets;

use App\Models\DonationType;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DonationTypeStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Types', DonationType::count()),
            Stat::make('Active Types', DonationType::where('is_active', true)->count()),
            Stat::make('Recurring Types', DonationType::where('is_recurring', true)->where('is_active', true)->count()),
        ];
    }
}

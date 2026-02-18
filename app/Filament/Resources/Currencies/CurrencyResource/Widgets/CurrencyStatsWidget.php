<?php

namespace App\Filament\Resources\Currencies\CurrencyResource\Widgets;

use App\Models\Currency;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CurrencyStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalCurrencies = Currency::count();
        $usdExists = Currency::where('code', 'USD')->exists();
        $eurExists = Currency::where('code', 'EUR')->exists();
        $gbpExists = Currency::where('code', 'GBP')->exists();

        return [
            Stat::make('Total Currencies', $totalCurrencies)
                ->description('Active in system')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),
            Stat::make('Major Currencies', ($usdExists ? 'USD' : '') . ($eurExists ? ', EUR' : '') . ($gbpExists ? ', GBP' : ''))
                ->description('Standard ISO codes found')
                ->descriptionIcon('heroicon-m-globe-alt')
                ->color('success'),
        ];
    }
}

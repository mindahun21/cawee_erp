<?php

namespace App\Filament\Widgets\Finance;

use App\Traits\BelongsToModuleWidget;

use App\Models\Finance\BankAccount;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class FinanceCashPositionWidget extends BaseWidget
{
    use BelongsToModuleWidget;

    protected static ?int $sort = 1;
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $metrics = Cache::remember('finance:cash_position', now()->addMinutes(5), function () {
            $bankAccounts = BankAccount::where('is_active', true)->get();

            $totalETB = $bankAccounts
                ->filter(fn ($b) => $b->currency?->code === 'ETB' || $b->currency_id === null)
                ->sum('current_balance');

            $totalUSD = $bankAccounts
                ->filter(fn ($b) => $b->currency?->code === 'USD')
                ->sum('current_balance');

            $totalEUR = $bankAccounts
                ->filter(fn ($b) => $b->currency?->code === 'EUR')
                ->sum('current_balance');

            $totalAccounts = $bankAccounts->count();
            $projectAccounts = $bankAccounts->where('account_type', 'project_specific')->count();

            return compact('totalETB', 'totalUSD', 'totalEUR', 'totalAccounts', 'projectAccounts');
        });

        return [
            Stat::make('Cash Position (ETB)', 'ETB ' . number_format((float)($metrics['totalETB'] ?? 0), 2))
                ->description('Total ETB across all active bank accounts')
                ->descriptionIcon('heroicon-m-building-library')
                ->color('success'),

            Stat::make('Cash Position (USD)', 'USD ' . number_format((float)($metrics['totalUSD'] ?? 0), 2))
                ->description('Total USD-denominated accounts')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('primary'),

            Stat::make('Cash Position (EUR)', 'EUR ' . number_format((float)($metrics['totalEUR'] ?? 0), 2))
                ->description('Total EUR-denominated accounts')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('info'),

            Stat::make('Active Bank Accounts', (int)($metrics['totalAccounts'] ?? 0))
                ->description("of which " . (int)($metrics['projectAccounts'] ?? 0) . " are project-specific")
                ->descriptionIcon('heroicon-m-credit-card')
                ->color('gray'),
        ];
    }
}

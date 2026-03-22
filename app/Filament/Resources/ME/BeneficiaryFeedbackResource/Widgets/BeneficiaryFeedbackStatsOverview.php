<?php

declare(strict_types=1);

namespace App\Filament\Resources\ME\BeneficiaryFeedbackResource\Widgets;

use App\Models\ME\MeBeneficiaryFeedback;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class BeneficiaryFeedbackStatsOverview extends BaseWidget
{
    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $total      = MeBeneficiaryFeedback::query()->count();
        $positive   = MeBeneficiaryFeedback::query()->where('sentiment', 'positive')->count();
        $neutral    = MeBeneficiaryFeedback::query()->where('sentiment', 'neutral')->count();
        $negative   = MeBeneficiaryFeedback::query()->where('sentiment', 'negative')->count();
        $avgRating  = MeBeneficiaryFeedback::query()->whereNotNull('rating')->avg('rating');
        $thisMonth  = MeBeneficiaryFeedback::query()
            ->whereMonth('submitted_at', now()->month)
            ->whereYear('submitted_at', now()->year)
            ->count();

        $positivityRate = $total > 0
            ? round(($positive / $total) * 100, 1)
            : 0;

        return [
            Stat::make('Total Feedback Entries', Number::format($total))
                ->description('All time across all projects')
                ->icon('heroicon-o-chat-bubble-bottom-center-text')
                ->color('primary'),

            Stat::make('This Month', Number::format($thisMonth))
                ->description('Entries recorded in ' . now()->format('F Y'))
                ->icon('heroicon-o-calendar')
                ->color('info'),

            Stat::make('Positivity Rate', $positivityRate . '%')
                ->description("{$positive} positive  ·  {$neutral} neutral  ·  {$negative} negative")
                ->icon('heroicon-o-face-smile')
                ->color($positivityRate >= 60 ? 'success' : ($positivityRate >= 40 ? 'warning' : 'danger')),

            Stat::make('Average Rating', $avgRating ? number_format((float) $avgRating, 1) . ' / 5' : '—')
                ->description($avgRating ? str_repeat('★', (int) round((float) $avgRating)) . str_repeat('☆', 5 - (int) round((float) $avgRating)) : 'No ratings yet')
                ->icon('heroicon-o-star')
                ->color($avgRating >= 4 ? 'success' : ($avgRating >= 3 ? 'warning' : 'danger')),
        ];
    }
}

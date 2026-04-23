<?php

namespace App\Filament\Widgets\Finance;

use App\Models\Finance\GeneralLedger;
use App\Models\Finance\AccountingPeriod;
use App\Models\Finance\Budget;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class FinanceBurnRateChartWidget extends ChartWidget
{
    protected ?string $heading = 'Burn Rate — Actual vs. Budget (Last 6 Months)';
    protected static ?int $sort = 3;
    protected ?string $maxHeight = '320px';
    protected int|string|array $columnSpan = 2;

    protected function getData(): array
    {
        $data = Cache::remember('finance:burn_rate_chart', now()->addMinutes(10), function () {
            $months = collect();
            for ($i = 5; $i >= 0; $i--) {
                $months->push(now()->subMonths($i)->startOfMonth());
            }

            $labels  = [];
            $actuals = [];
            $budgets = [];

            foreach ($months as $month) {
                $labels[] = $month->format('M Y');

                // Actual: sum of debits in GL for expense-type accounts in this month
                $actual = (float) GeneralLedger::whereMonth('transaction_date', $month->month)
                    ->whereYear('transaction_date', $month->year)
                    ->sum('debit');

                $actuals[] = round($actual, 2);

                // Budget: active budgets' total_budget_amount / 12 (monthly allocation estimate)
                $monthlyBudget = (float) Budget::where('status', 'active')
                    ->whereYear('created_at', $month->year)
                    ->sum('total_budget_amount');

                $budgets[] = round($monthlyBudget / 12, 2);
            }

            return compact('labels', 'actuals', 'budgets');
        });

        return [
            'datasets' => [
                [
                    'label'           => 'Actual Expenditure (ETB)',
                    'data'            => $data['actuals'],
                    'borderColor'     => 'rgba(239,68,68,0.9)',
                    'backgroundColor' => 'rgba(239,68,68,0.1)',
                    'borderWidth'     => 2,
                    'tension'         => 0.4,
                    'fill'            => true,
                ],
                [
                    'label'           => 'Budget Allocation (ETB)',
                    'data'            => $data['budgets'],
                    'borderColor'     => 'rgba(59,130,246,0.9)',
                    'backgroundColor' => 'rgba(59,130,246,0.1)',
                    'borderWidth'     => 2,
                    'tension'         => 0.4,
                    'fill'            => true,
                    'borderDash'      => [6, 3],
                ],
            ],
            'labels' => $data['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}

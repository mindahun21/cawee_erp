<?php

namespace App\Filament\Widgets\Finance;

use App\Models\Finance\GeneralLedger;
use App\Models\Finance\ChartOfAccount;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class FinanceTopExpensesWidget extends ChartWidget
{
    protected ?string $heading = 'Top Expense Categories (This Month)';
    protected static ?int $sort = 6;
    protected ?string $maxHeight = '320px';
    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        $data = Cache::remember('finance:top_expenses', now()->addMinutes(10), function () {
            $startOfMonth = now()->startOfMonth()->toDateString();
            $endOfMonth   = now()->endOfMonth()->toDateString();

            // Group GL debits by account type name this month
            $rows = GeneralLedger::whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
                ->where('debit', '>', 0)
                ->join('finance_chart_of_accounts', 'finance_general_ledgers.account_id', '=', 'finance_chart_of_accounts.id')
                ->join('finance_account_types', 'finance_chart_of_accounts.account_type_id', '=', 'finance_account_types.id')
                ->select('finance_account_types.name as type_name', DB::raw('SUM(finance_general_ledgers.debit) as total'))
                ->groupBy('finance_account_types.name')
                ->orderByDesc('total')
                ->limit(7)
                ->get();

            $labels = $rows->pluck('type_name')->toArray();
            $values = $rows->map(fn ($r) => round((float)$r->total, 2))->toArray();

            return compact('labels', 'values');
        });

        $colors = [
            'rgba(239,68,68,0.8)',
            'rgba(245,158,11,0.8)',
            'rgba(16,185,129,0.8)',
            'rgba(59,130,246,0.8)',
            'rgba(139,92,246,0.8)',
            'rgba(236,72,153,0.8)',
            'rgba(20,184,166,0.8)',
        ];

        return [
            'datasets' => [[
                'label'           => 'Expenditure (ETB)',
                'data'            => $data['values'],
                'backgroundColor' => array_slice($colors, 0, count($data['values'])),
                'borderWidth'     => 1,
                'borderColor'     => '#ffffff',
            ]],
            'labels' => $data['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}

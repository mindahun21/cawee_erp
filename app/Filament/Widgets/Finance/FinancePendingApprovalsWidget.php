<?php

namespace App\Filament\Widgets\Finance;

use App\Traits\BelongsToModuleWidget;

use App\Models\Finance\PaymentRequisition;
use App\Models\Finance\PaymentVoucher;
use App\Models\Finance\PettyCashReplenishment;
use App\Models\Finance\PerdiemRequest;
use App\Models\Finance\JournalEntry;
use App\Models\Finance\Budget;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class FinancePendingApprovalsWidget extends BaseWidget
{
    use BelongsToModuleWidget;

    protected static ?int $sort = 2;
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $metrics = Cache::remember('finance:pending_approvals', now()->addMinutes(3), function () {
            return [
                'pr'    => PaymentRequisition::where('status', 'pending_approval')->count(),
                'pv'    => PaymentVoucher::where('status', 'pending_approval')->count(),
                'pcr'   => PettyCashReplenishment::where('status', 'pending')->count(),
                'pdr'   => PerdiemRequest::where('status', 'pending')->count(),
                'je'    => JournalEntry::where('status', 'draft')->count(),
                'budget'=> Budget::where('status', 'draft')->count(),
            ];
        });

        $totalPending = array_sum($metrics);

        return [
            Stat::make('Payment Requisitions Pending', (int)$metrics['pr'])
                ->description('Awaiting finance officer approval')
                ->descriptionIcon($metrics['pr'] > 0 ? 'heroicon-m-clock' : 'heroicon-m-check-circle')
                ->color($metrics['pr'] > 0 ? 'danger' : 'success'),

            Stat::make('Payment Vouchers Pending', (int)$metrics['pv'])
                ->description('Awaiting approval before GL posting')
                ->descriptionIcon($metrics['pv'] > 0 ? 'heroicon-m-clock' : 'heroicon-m-check-circle')
                ->color($metrics['pv'] > 0 ? 'warning' : 'success'),

            Stat::make('Petty Cash Replenishments', (int)$metrics['pcr'])
                ->description('Replenishment requests pending')
                ->descriptionIcon($metrics['pcr'] > 0 ? 'heroicon-m-inbox-arrow-down' : 'heroicon-m-check')
                ->color($metrics['pcr'] > 0 ? 'warning' : 'success'),

            Stat::make('Per Diem Requests', (int)$metrics['pdr'])
                ->description('Awaiting supervisor / finance approval')
                ->descriptionIcon($metrics['pdr'] > 0 ? 'heroicon-m-map-pin' : 'heroicon-m-check')
                ->color($metrics['pdr'] > 0 ? 'warning' : 'success'),

            Stat::make('Draft Journal Entries', (int)$metrics['je'])
                ->description('Unposted manual journal entries')
                ->descriptionIcon('heroicon-m-document-text')
                ->color($metrics['je'] > 0 ? 'info' : 'success'),

            Stat::make('Total Items Awaiting Action', $totalPending)
                ->description('Across all Finance documents')
                ->descriptionIcon('heroicon-m-bell-alert')
                ->color($totalPending > 0 ? 'danger' : 'success'),
        ];
    }
}

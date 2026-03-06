<?php

namespace App\Filament\Widgets\Procurement;

use App\Models\Procurement\Contract;
use App\Models\Procurement\Invoice;
use App\Models\Procurement\Payment;
use App\Models\Procurement\ProcurementBudget;
use App\Models\Procurement\PurchaseOrder;
use App\Models\Procurement\Requisition;
use App\Models\Procurement\Supplier;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProcurementStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $year = now()->year;
        $reqPending      = Requisition::where('overall_status', Requisition::STATUS_SUBMITTED)->count();
        $poValueYTD      = PurchaseOrder::whereYear('created_at', $year)->sum('total_amount');
        $overdueInvoices = Invoice::whereDate('due_date', '<', now())->whereNotIn('status', ['Paid', 'Rejected'])->count();
        $budgets         = ProcurementBudget::where('status', 'Active')->get();
        $totalAllocated  = (float) $budgets->sum('allocated_amount');
        $utilizationPct  = $totalAllocated > 0
            ? round(((float)$budgets->sum('expended_amount') + (float)$budgets->sum('committed_amount')) / $totalAllocated * 100, 1)
            : 0;
        $supplierCount     = Supplier::where('status', 'Active')->count();
        $contractsExpiring = Contract::where('status', 'Active')->whereDate('expiry_date', '<=', now()->addDays(30))->whereDate('expiry_date', '>=', now())->count();
        $paymentsPending   = Payment::where('status', 'Pending Approval')->count();

        return [
            Stat::make('Requisitions Awaiting Approval', $reqPending)
                ->description($reqPending > 0 ? 'Pending in approval queue' : 'All up to date ✓')
                ->descriptionIcon($reqPending > 0 ? 'heroicon-m-clock' : 'heroicon-m-check-circle')
                ->color($reqPending > 0 ? 'warning' : 'success'),

            Stat::make('PO Value YTD ' . $year, 'ETB ' . number_format($poValueYTD, 0))
                ->description('Total purchase order value')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('primary'),

            Stat::make('Overdue Invoices', $overdueInvoices)
                ->description($overdueInvoices > 0 ? 'Need immediate attention ⚠️' : 'No overdue invoices ✓')
                ->descriptionIcon($overdueInvoices > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check')
                ->color($overdueInvoices > 0 ? 'danger' : 'success'),

            Stat::make('Budget Utilization', $utilizationPct . '%')
                ->description('Committed + expended vs allocated')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color(match (true) { $utilizationPct >= 90 => 'danger', $utilizationPct >= 70 => 'warning', default => 'success' }),

            Stat::make('Active Suppliers', $supplierCount)
                ->description('Registered active vendors')
                ->descriptionIcon('heroicon-m-building-storefront')
                ->color('info'),

            Stat::make('Contracts Expiring (30 days)', $contractsExpiring)
                ->description($contractsExpiring > 0 ? 'Requires renewal action!' : 'No contracts expiring soon')
                ->descriptionIcon($contractsExpiring > 0 ? 'heroicon-m-calendar-days' : 'heroicon-m-check-badge')
                ->color($contractsExpiring > 0 ? 'warning' : 'success'),

            Stat::make('Payments Awaiting Authorization', $paymentsPending)
                ->description($paymentsPending > 0 ? 'Pending Finance / Director sign-off' : 'All payments authorized ✓')
                ->descriptionIcon($paymentsPending > 0 ? 'heroicon-m-banknotes' : 'heroicon-m-check')
                ->color($paymentsPending > 0 ? 'warning' : 'success'),
        ];
    }
}

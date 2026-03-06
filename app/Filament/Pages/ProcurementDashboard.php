<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Procurement\ProcurementBudgetUtilizationChart;
use App\Filament\Widgets\Procurement\ProcurementPoByStatusChart;
use App\Filament\Widgets\Procurement\ProcurementRecentRequisitionsWidget;
use App\Filament\Widgets\Procurement\ProcurementStatsOverview;
use App\Filament\Widgets\Procurement\ProcurementTenderPipelineWidget;
use App\Models\Procurement\Contract;
use App\Models\Procurement\GoodsReceipt;
use App\Models\Procurement\Invoice;
use App\Models\Procurement\Payment;
use App\Models\Procurement\ProcurementBudget;
use App\Models\Procurement\PurchaseOrder;
use App\Models\Procurement\Requisition;
use App\Models\Procurement\Supplier;
use App\Models\Procurement\Tender;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class ProcurementDashboard extends Page
{
    protected string $view = 'filament.pages.procurement-dashboard';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static string|UnitEnum|null $navigationGroup = 'Procurement';

    protected static ?string $navigationLabel = 'Procurement Dashboard';

    protected static ?int $navigationSort = 0;

    protected static ?string $title = 'Procurement Dashboard';

    public function getHeaderWidgets(): array { return []; }

    protected function getWidgets(): array
    {
        return [
            ProcurementStatsOverview::class,
            ProcurementBudgetUtilizationChart::class,
            ProcurementPoByStatusChart::class,
            ProcurementTenderPipelineWidget::class,
            ProcurementRecentRequisitionsWidget::class,
        ];
    }

    public function getColumns(): int|array { return 3; }

    protected function getViewData(): array
    {
        $currentYear = date('Y');
        $reqPending     = Requisition::where('overall_status', Requisition::STATUS_SUBMITTED)->count();
        $reqThisMonth   = Requisition::whereMonth('created_at', now()->month)->whereYear('created_at', $currentYear)->count();
        $poPending      = PurchaseOrder::where('overall_status', PurchaseOrder::STATUS_PENDING)->count();
        $poValueYTD     = PurchaseOrder::whereYear('created_at', $currentYear)->sum('total_amount');
        $invoiceOverdue = Invoice::whereDate('due_date', '<', now())->whereNotIn('status', ['Paid', 'Rejected'])->count();
        $invoicePending = Invoice::where('finance_status', 'Pending')->count();
        $paymentPending = Payment::where('status', 'Pending Approval')->count();
        $paymentsThisMonth = Payment::where('status', 'Processed')->whereMonth('payment_date', now()->month)->sum('amount');
        $budgets        = ProcurementBudget::where('status', 'Active')->get();
        $totalAllocated = (float) $budgets->sum('allocated_amount');
        $totalExpended  = (float) $budgets->sum('expended_amount');
        $totalCommitted = (float) $budgets->sum('committed_amount');
        $utilizationPct = $totalAllocated > 0 ? round(($totalExpended + $totalCommitted) / $totalAllocated * 100, 1) : 0;
        $supplierCount      = Supplier::where('status', 'Active')->count();
        $contractsActive    = Contract::where('status', 'Active')->count();
        $contractsExpiring  = Contract::where('status', 'Active')->whereDate('expiry_date', '<=', now()->addDays(30))->whereDate('expiry_date', '>=', now())->count();
        $tendersOpen    = Tender::where('status', 'Published')->count();
        $grnPending     = GoodsReceipt::whereIn('status', ['Draft', 'Inspecting'])->count();

        return compact(
            'reqPending', 'reqThisMonth', 'poPending', 'poValueYTD',
            'invoiceOverdue', 'invoicePending', 'paymentPending', 'paymentsThisMonth',
            'totalAllocated', 'totalExpended', 'totalCommitted', 'utilizationPct',
            'supplierCount', 'contractsActive', 'contractsExpiring',
            'tendersOpen', 'grnPending', 'currentYear'
        );
    }
}

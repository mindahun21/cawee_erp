<?php

namespace App\Services\AI\Data;

use App\Models\Procurement\Requisition;
use App\Models\Procurement\PurchaseOrder;
use App\Models\Procurement\Tender;
use App\Models\Procurement\Bid;
use App\Models\Procurement\Invoice;
use App\Models\Procurement\Payment;
use App\Models\Procurement\Contract;
use App\Models\Procurement\Supplier;
use App\Models\Procurement\GoodsReceipt;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;

class ProcurementDataPreloader extends ModuleDataPreloader
{
    public function getModuleName(): string
    {
        return 'procurement';
    }

    public function getRequiredPermission(): string
    {
        return 'View:ProcurementDashboard';
    }

    protected function getStatusValues(): array
    {
        return [
            'requisitions' => ['Draft', 'Submitted', 'Approved', 'Rejected', 'Converted to PO'],
            'purchase_orders' => ['Draft', 'Pending Approval', 'Approved', 'Sent to Supplier', 
                'Acknowledged', 'Partially Received', 'Received', 'Closed', 'Cancelled'],
            'tenders' => ['Draft', 'Published', 'Closed', 'Awarded', 'Cancelled'],
            'invoices' => ['Draft', 'Pending', 'Approved', 'Paid', 'Overdue', 'Cancelled'],
        ];
    }

    /**
     * Build a structured text snapshot of live procurement data
     */
    public function snapshot(array $filters = []): string
    {
        $lines = [];
        $lines[] = '=== LIVE PROCUREMENT DATA SNAPSHOT (as of ' . now()->toDateTimeString() . ') ===';
        $lines[] = '';

        // ── Requisitions ──────────────────────────────────────
        $reqsByStatus = Requisition::query()
            ->select('overall_status', DB::raw('COUNT(*) as cnt'))
            ->groupBy('overall_status')
            ->pluck('cnt', 'overall_status')
            ->toArray();
            
        $lines[] = '## Requisitions';
        foreach ($reqsByStatus as $status => $count) {
            $lines[] = "  {$status}: {$count}";
        }
        $lines[] = "  total: " . array_sum($reqsByStatus);
        $lines[] = '';

        // Pending requisitions detail
        $pendingReqs = Requisition::query()
            ->where('overall_status', 'Submitted')
            ->with('requester')
            ->orderBy('created_at', 'desc')
            ->limit(isset($filters['limit']) ? min(100, max(1, (int)$filters['limit'])) : 10)
            ->get();
            
        if ($pendingReqs->isNotEmpty()) {
            $lines[] = '## Pending Requisitions (awaiting approval)';
            foreach ($pendingReqs as $req) {
                $requester = $req->requester?->name ?? 'N/A';
                $stage = $req->current_stage ?? 'Unknown';
                $lines[] = "  - [{$req->requisition_number}] Dept: {$req->department} | Requester: {$requester} | Amount: " . $this->formatDecimal($req->estimated_total ?? 0) . " | Stage: {$stage}";
            }
            $lines[] = '';
        }

        // ── Purchase Orders ───────────────────────────────────
        $posByStatus = PurchaseOrder::query()
            ->select('overall_status', DB::raw('COUNT(*) as cnt'))
            ->groupBy('overall_status')
            ->pluck('cnt', 'overall_status')
            ->toArray();
            
        $lines[] = '## Purchase Orders';
        foreach ($posByStatus as $status => $count) {
            $lines[] = "  {$status}: {$count}";
        }
        $lines[] = "  total: " . array_sum($posByStatus);
        $lines[] = '';

        // Active POs detail
        $activePOs = PurchaseOrder::query()
            ->whereIn('overall_status', ['Approved', 'Sent to Supplier', 'Acknowledged', 'Partially Received'])
            ->with('supplier')
            ->orderBy('order_date', 'desc')
            ->limit(isset($filters['limit']) ? min(100, max(1, (int)$filters['limit'])) : 10)
            ->get();
            
        if ($activePOs->isNotEmpty()) {
            $lines[] = '## Active Purchase Orders';
            foreach ($activePOs as $po) {
                $supplier = $po->supplier?->name ?? 'N/A';
                $lines[] = "  - [{$po->po_number}] Supplier: {$supplier} | Amount: " . $this->formatDecimal($po->total_amount) . " {$po->currency} | Status: {$po->overall_status} | Delivery: " . ($po->delivery_date?->format('Y-m-d') ?? 'N/A');
            }
            $lines[] = '';
        }

        // ── Tenders ───────────────────────────────────────────
        $tendersByStatus = Tender::query()
            ->select('status', DB::raw('COUNT(*) as cnt'))
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->toArray();
            
        $lines[] = '## Tenders';
        foreach ($tendersByStatus as $status => $count) {
            $lines[] = "  {$status}: {$count}";
        }
        $lines[] = "  total: " . array_sum($tendersByStatus);
        $lines[] = '';

        // Open tenders
        $openTenders = Tender::query()
            ->where('status', 'Published')
            ->withCount('bids')
            ->orderBy('submission_deadline', 'asc')
            ->limit(5)
            ->get();
            
        if ($openTenders->isNotEmpty()) {
            $lines[] = '## Open Tenders (accepting bids)';
            foreach ($openTenders as $tender) {
                $lines[] = "  - [{$tender->tender_number}] {$tender->title} | Method: {$tender->method} | Bids: {$tender->bids_count} | Deadline: " . ($tender->submission_deadline?->format('Y-m-d') ?? 'N/A');
            }
            $lines[] = '';
        }

        // ── Invoices ──────────────────────────────────────────
        $invoicesByStatus = Invoice::query()
            ->select('status', DB::raw('COUNT(*) as cnt'))
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->toArray();
            
        $lines[] = '## Invoices';
        foreach ($invoicesByStatus as $status => $count) {
            $lines[] = "  {$status}: {$count}";
        }
        $lines[] = "  total: " . array_sum($invoicesByStatus);
        $lines[] = '';

        // Overdue invoices
        $overdueInvoices = Invoice::query()
            ->where('status', '!=', 'Paid')
            ->where('status', '!=', 'Cancelled')
            ->where('due_date', '<', now())
            ->with('supplier')
            ->orderBy('due_date', 'asc')
            ->limit(10)
            ->get();
            
        if ($overdueInvoices->isNotEmpty()) {
            $lines[] = '## Overdue Invoices (requires attention)';
            foreach ($overdueInvoices as $inv) {
                $supplier = $inv->supplier?->name ?? 'N/A';
                $daysOverdue = now()->diffInDays($inv->due_date);
                $lines[] = "  - [{$inv->invoice_number}] Supplier: {$supplier} | Amount: " . $this->formatDecimal($inv->balance) . " {$inv->currency} | Overdue: {$daysOverdue} days";
            }
            $lines[] = '';
        }

        // ── Payments ──────────────────────────────────────────
        $totalPayments = Payment::count();
        $paymentsThisMonth = Payment::where('payment_date', '>=', now()->startOfMonth())->count();
        $amountPaidThisMonth = Payment::where('payment_date', '>=', now()->startOfMonth())->sum('amount');
        
        $lines[] = '## Payments';
        $lines[] = "  total_payments: {$totalPayments}";
        $lines[] = "  payments_this_month: {$paymentsThisMonth}";
        $lines[] = "  amount_paid_this_month: " . $this->formatDecimal($amountPaidThisMonth);
        $lines[] = '';

        // ── Suppliers ─────────────────────────────────────────
        $suppliersByStatus = Supplier::query()
            ->select('status', DB::raw('COUNT(*) as cnt'))
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->toArray();
            
        $lines[] = '## Suppliers';
        foreach ($suppliersByStatus as $status => $count) {
            $lines[] = "  {$status}: {$count}";
        }
        $lines[] = "  total: " . array_sum($suppliersByStatus);
        $lines[] = '';

        // Top suppliers by PO count
        $topSuppliers = Supplier::query()
            ->where('status', 'Active')
            ->withCount(['purchaseOrders' => function ($q) {
                $q->where('created_at', '>=', now()->subMonths(6));
            }])
            ->having('purchase_orders_count', '>', 0)
            ->orderByDesc('purchase_orders_count')
            ->limit(10)
            ->get();
            
        if ($topSuppliers->isNotEmpty()) {
            $lines[] = '## Top Suppliers (by PO count, last 6 months)';
            foreach ($topSuppliers as $supplier) {
                $lines[] = "  {$supplier->name}: {$supplier->purchase_orders_count} POs";
            }
            $lines[] = '';
        }

        // ── Contracts ─────────────────────────────────────────
        $contractsByStatus = Contract::query()
            ->select('status', DB::raw('COUNT(*) as cnt'))
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->toArray();
            
        $lines[] = '## Contracts';
        foreach ($contractsByStatus as $status => $count) {
            $lines[] = "  {$status}: {$count}";
        }
        $lines[] = '';

        // Expiring contracts
        $expiringContracts = Contract::query()
            ->where('status', 'Active')
            ->where('end_date', '<=', now()->addDays(30))
            ->where('end_date', '>=', now())
            ->with('supplier')
            ->orderBy('end_date', 'asc')
            ->limit(5)
            ->get();
            
        if ($expiringContracts->isNotEmpty()) {
            $lines[] = '## Contracts Expiring Soon (next 30 days)';
            foreach ($expiringContracts as $contract) {
                $supplier = $contract->supplier?->name ?? 'N/A';
                $daysLeft = now()->diffInDays($contract->end_date);
                $lines[] = "  - [{$contract->contract_number}] {$contract->title} | Supplier: {$supplier} | Expires in: {$daysLeft} days | Value: " . $this->formatDecimal($contract->contract_value) . " {$contract->currency}";
            }
            $lines[] = '';
        }

        // ── Procurement Metrics ───────────────────────────────
        $lines[] = '## Procurement Metrics (last 30 days)';
        
        $recentReqs = Requisition::where('created_at', '>=', now()->subDays(30))->count();
        $recentPOs = PurchaseOrder::where('created_at', '>=', now()->subDays(30))->count();
        $recentTenders = Tender::where('created_at', '>=', now()->subDays(30))->count();
        
        $avgReqApprovalTime = Requisition::query()
            ->where('overall_status', 'Approved')
            ->where('procurement_approved_at', '>=', now()->subDays(30))
            ->selectRaw('AVG(TIMESTAMPDIFF(DAY, created_at, procurement_approved_at)) as avg_days')
            ->value('avg_days');
            
        $lines[] = "  new_requisitions: {$recentReqs}";
        $lines[] = "  new_purchase_orders: {$recentPOs}";
        $lines[] = "  new_tenders: {$recentTenders}";
        $lines[] = "  avg_requisition_approval_time: " . ($avgReqApprovalTime ? round($avgReqApprovalTime, 1) . " days" : "N/A");
        $lines[] = '';

        return implode("\n", $lines);
    }

    protected function applySearchFilter(Builder $query, string $search): void
    {
        $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhere('requisition_number', 'like', "%{$search}%")
              ->orWhere('po_number', 'like', "%{$search}%")
              ->orWhere('tender_number', 'like', "%{$search}%");
        });
    }
}


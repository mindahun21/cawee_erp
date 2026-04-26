<?php

namespace App\Filament\Pages;

use App\Traits\BelongsToModulePage;

use App\Exports\GeneralReportExport;
use App\Models\Currency;
use App\Models\Procurement\Invoice;
use App\Models\Procurement\Payment;
use App\Models\Procurement\PurchaseOrder;
use App\Models\Procurement\PurchaseOrderItem;
use App\Models\Procurement\Requisition;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use UnitEnum;

class ProcurementReports extends Page implements HasTable
{
    use BelongsToModulePage;

    use InteractsWithTable;

    protected string $view = 'filament.pages.procurement-reports';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static string|UnitEnum|null $navigationGroup = 'Procurement';

    protected static ?string $navigationLabel = 'Reports';

    protected static ?int $navigationSort = 95;

    protected static ?string $title = 'Procurement Reports';

    public function mount(): void
    {
        $this->mountInteractsWithTable();
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading($this->getSelectedReportHeading())
            ->description($this->getSelectedReportDescription())
            ->defaultKeySort(fn (): bool => request()->query('report', 'invoices') !== 'cost-by-item')
            ->columns($this->getReportColumns())
            ->defaultSort($this->getReportDefaultSortColumn(), $this->getReportDefaultSortDirection());
    }

    protected function getTableQuery(): Builder
    {
        [$startDate, $endDate] = $this->resolveDateRange(request()->query('period', 'this_month'));
        $currency = request()->query('currency', 'ALL');
        $report = request()->query('report', 'invoices');

        if (in_array($report, ['chart-stats-count', 'chart-stats-cost'], true)) {
            return Invoice::query()->whereRaw('1 = 0');
        }

        return match ($report) {
            'purchase-orders' => PurchaseOrder::query()
                ->with(['supplier', 'requisition'])
                ->when($currency !== 'ALL', fn (Builder $q) => $q->where('currency', $currency))
                ->when($startDate && $endDate, fn (Builder $q) => $q->whereBetween('order_date', [$startDate, $endDate])),

            'line-items' => PurchaseOrderItem::query()
                ->with('purchaseOrder')
                ->whereHas('purchaseOrder', function (Builder $q) use ($currency, $startDate, $endDate) {
                    if ($currency !== 'ALL') {
                        $q->where('currency', $currency);
                    }
                    if ($startDate && $endDate) {
                        $q->whereBetween('order_date', [$startDate, $endDate]);
                    }
                }),

            'cost-by-item' => PurchaseOrderItem::query()
                ->selectRaw('MIN(id) as id, description, unit, COUNT(*) as line_count, SUM(quantity) as quantity, SUM(line_total) as total')
                ->whereHas('purchaseOrder', function (Builder $q) use ($currency, $startDate, $endDate) {
                    if ($currency !== 'ALL') {
                        $q->where('currency', $currency);
                    }
                    if ($startDate && $endDate) {
                        $q->whereBetween('order_date', [$startDate, $endDate]);
                    }
                })
                ->groupBy('description', 'unit'),

            'payments' => Payment::query()
                ->with(['invoice', 'supplier'])
                ->when($currency !== 'ALL', fn (Builder $q) => $q->where('currency', $currency))
                ->when($startDate && $endDate, fn (Builder $q) => $q->whereBetween('payment_date', [$startDate, $endDate])),

            'requisitions' => Requisition::query()
                ->with('requester')
                ->when($startDate && $endDate, fn (Builder $q) => $q->whereBetween('created_at', [$startDate, $endDate])),

            default => Invoice::query()
                ->with(['purchaseOrder.contract', 'supplier'])
                ->when($currency !== 'ALL', fn (Builder $q) => $q->where('currency', $currency))
                ->when($startDate && $endDate, fn (Builder $q) => $q->whereBetween('invoice_date', [$startDate, $endDate])),
        };
    }

    /**
     * @return array<int, \Filament\Tables\Columns\Column>
     */
    protected function getReportColumns(): array
    {
        return match (request()->query('report', 'invoices')) {
            'purchase-orders' => [
                TextColumn::make('po_number')->label('PO #')->searchable()->sortable()->weight('semibold'),
                TextColumn::make('requisition.requisition_number')->label('Requisition')->toggleable()->placeholder('—'),
                TextColumn::make('supplier.name')->label('Supplier')->searchable()->toggleable()->placeholder('—'),
                TextColumn::make('order_date')->label('Order Date')->date()->sortable(),
                TextColumn::make('overall_status')->label('Status')->badge()->color('gray'),
                TextColumn::make('subtotal')->money(fn () => request()->query('currency', 'ETB') === 'ALL' ? 'ETB' : request()->query('currency', 'ETB'))->alignEnd()->toggleable(),
                TextColumn::make('tax_amount')->money(fn () => request()->query('currency', 'ETB') === 'ALL' ? 'ETB' : request()->query('currency', 'ETB'))->alignEnd()->toggleable(),
                TextColumn::make('total_amount')->money(fn () => request()->query('currency', 'ETB') === 'ALL' ? 'ETB' : request()->query('currency', 'ETB'))->alignEnd()->sortable(),
            ],

            'line-items' => [
                TextColumn::make('purchaseOrder.po_number')->label('PO #')->searchable()->sortable()->placeholder('—'),
                TextColumn::make('description')->label('Description')->wrap()->searchable()->placeholder('—'),
                TextColumn::make('unit')->label('Unit')->toggleable()->placeholder('—'),
                TextColumn::make('quantity')->label('Quantity')->numeric(decimalPlaces: 2)->alignEnd()->sortable(),
                TextColumn::make('unit_price')->label('Unit Price')->numeric(decimalPlaces: 2)->alignEnd()->toggleable(),
                TextColumn::make('tax_rate')->label('Tax %')->numeric(decimalPlaces: 2)->alignEnd()->toggleable(),
                TextColumn::make('line_total')->label('Line Total')->numeric(decimalPlaces: 2)->alignEnd()->sortable(),
            ],

            'cost-by-item' => [
                TextColumn::make('description')->label('Description')->wrap()->searchable()->placeholder('—'),
                TextColumn::make('unit')->label('Unit')->toggleable()->placeholder('—'),
                TextColumn::make('line_count')->label('Lines')->numeric()->alignEnd()->sortable(),
                TextColumn::make('quantity')->label('Quantity')->numeric(decimalPlaces: 2)->alignEnd()->sortable(),
                TextColumn::make('total')->label('Total Cost')->numeric(decimalPlaces: 2)->alignEnd()->sortable(),
            ],

            'payments' => [
                TextColumn::make('payment_reference')->label('Reference')->searchable()->sortable()->weight('semibold'),
                TextColumn::make('supplier.name')->label('Supplier')->searchable()->toggleable()->placeholder('—'),
                TextColumn::make('payment_date')->label('Payment Date')->date()->sortable(),
                TextColumn::make('status')->label('Status')->badge()->color('gray'),
                TextColumn::make('amount')->label('Amount')->numeric(decimalPlaces: 2)->alignEnd()->sortable(),
            ],

            'requisitions' => [
                TextColumn::make('requisition_number')->label('Requisition #')->searchable()->sortable()->weight('semibold'),
                TextColumn::make('category')->label('Category')->toggleable()->placeholder('—'),
                TextColumn::make('requester.name')->label('Requested By')->toggleable()->placeholder('—'),
                TextColumn::make('overall_status')->label('Status')->badge()->color('gray'),
                TextColumn::make('estimated_total')->label('Est. Total')->numeric(decimalPlaces: 2)->alignEnd()->sortable(),
            ],

            default => [
                TextColumn::make('invoice_number')->label('Invoice #')->searchable()->sortable()->weight('semibold'),
                TextColumn::make('purchaseOrder.contract.contract_number')->label('Contract')->toggleable()->placeholder('—'),
                TextColumn::make('purchaseOrder.po_number')->label('PO')->toggleable()->placeholder('—'),
                TextColumn::make('supplier.name')->label('Supplier')->searchable()->toggleable()->placeholder('—'),
                TextColumn::make('invoice_date')->label('Invoice Date')->date()->sortable(),
                TextColumn::make('status')->label('Status')->badge()->color('gray'),
                TextColumn::make('subtotal')->money(fn () => request()->query('currency', 'ETB') === 'ALL' ? 'ETB' : request()->query('currency', 'ETB'))->alignEnd()->toggleable(),
                TextColumn::make('tax_amount')->money(fn () => request()->query('currency', 'ETB') === 'ALL' ? 'ETB' : request()->query('currency', 'ETB'))->alignEnd()->toggleable(),
                TextColumn::make('total_amount')->money(fn () => request()->query('currency', 'ETB') === 'ALL' ? 'ETB' : request()->query('currency', 'ETB'))->alignEnd()->sortable(),
            ],
        };
    }

    protected function getReportDefaultSortColumn(): string
    {
        return match (request()->query('report', 'invoices')) {
            'purchase-orders' => 'order_date',
            'line-items' => 'id',
            'cost-by-item' => 'total',
            'payments' => 'payment_date',
            'requisitions' => 'created_at',
            default => 'invoice_date',
        };
    }

    protected function getReportDefaultSortDirection(): string
    {
        return 'desc';
    }

    private function getSelectedReportHeading(): string
    {
        return match (request()->query('report', 'invoices')) {
            'purchase-orders' => 'Purchase Order Report',
            'line-items' => 'Purchase Order Line Items',
            'cost-by-item' => 'Cost by Item',
            'payments' => 'Payments Report',
            'requisitions' => 'Requisitions Report',
            'chart-stats-count', 'chart-stats-cost' => 'Statistics',
            default => 'Purchase Invoices Report',
        };
    }

    private function getSelectedReportDescription(): ?string
    {
        return match (request()->query('report', 'invoices')) {
            'purchase-orders' => 'Purchase orders by period and currency.',
            'line-items' => 'Line-level breakdown of purchase orders by product/description.',
            'cost-by-item' => 'Aggregated spend by product/description.',
            'payments' => 'Payments made in the selected period.',
            'requisitions' => 'Purchase requisitions in the selected period.',
            'chart-stats-count', 'chart-stats-cost' => null,
            default => 'High-level view of purchase invoices by period and currency.',
        };
    }

    protected function getHeaderActions(): array
    {
        $report = request()->query('report', 'invoices');

        if (in_array($report, ['chart-stats-count', 'chart-stats-cost'], true)) {
            return [];
        }

        return [
            Action::make('export_excel')
                ->label('Export Excel')
                ->icon('heroicon-o-table-cells')
                ->color('success')
                ->action(fn () => Excel::download(
                    $this->makeExport(),
                    'procurement_' . $report . '_' . now()->format('Ymd_His') . '.xlsx',
                )),
            Action::make('export_csv')
                ->label('Export CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn () => Excel::download(
                    $this->makeExport(),
                    'procurement_' . $report . '_' . now()->format('Ymd_His') . '.csv',
                    ExcelWriter::CSV,
                )),
        ];
    }

    private function makeExport(): GeneralReportExport
    {
        $columns = $this->getReportColumns();

        $headings = collect($columns)
            ->map(fn (TextColumn $column) => $column->getLabel() ?? $column->getName())
            ->values()
            ->all();

        $data = $this->getTableQuery()->get();

        $rows = $data->map(function ($record) use ($columns) {
            return collect($columns)
                ->map(function (TextColumn $column) use ($record) {
                    $state = $column->getState($record);

                    if ($state instanceof \DateTimeInterface) {
                        return $state->format('Y-m-d');
                    }

                    if (is_array($state)) {
                        return json_encode($state);
                    }

                    return $state;
                })
                ->all();
        })->all();

        return new GeneralReportExport($rows, $headings);
    }

    public function getHeaderWidgets(): array
    {
        return [];
    }

    public function getSubNavigation(): array
    {
        $params = [
            'currency' => request()->query('currency', 'ALL'),
            'period'   => request()->query('period', 'this_month'),
        ];

        $url = fn (string $report) => url()->current() . '?' . http_build_query(array_merge($params, ['report' => $report]));

        return [
            NavigationItem::make('Purchase Invoices Report')
                ->icon('heroicon-o-document-text')
                ->url($url('invoices'))
                ->isActiveWhen(fn () => (request()->query('report', 'invoices')) === 'invoices'),

            NavigationItem::make('Purchase Order Report')
                ->icon('heroicon-o-shopping-cart')
                ->url($url('purchase-orders'))
                ->isActiveWhen(fn () => request()->query('report') === 'purchase-orders'),

            NavigationItem::make('Purchase Order Line Items')
                ->icon('heroicon-o-list-bullet')
                ->url($url('line-items'))
                ->isActiveWhen(fn () => request()->query('report') === 'line-items'),

            NavigationItem::make('Cost by Item')
                ->icon('heroicon-o-calculator')
                ->url($url('cost-by-item'))
                ->isActiveWhen(fn () => request()->query('report') === 'cost-by-item'),

            NavigationItem::make('Payments Report')
                ->icon('heroicon-o-credit-card')
                ->url($url('payments'))
                ->isActiveWhen(fn () => request()->query('report') === 'payments'),

            NavigationItem::make('Requisitions Report')
                ->icon('heroicon-o-clipboard-document-list')
                ->url($url('requisitions'))
                ->isActiveWhen(fn () => request()->query('report') === 'requisitions'),

            NavigationItem::make('Purchase statistics by PO count')
                ->icon('heroicon-o-chart-bar')
                ->url($url('chart-stats-count'))
                ->isActiveWhen(fn () => request()->query('report') === 'chart-stats-count'),

            NavigationItem::make('Purchase statistics by cost')
                ->icon('heroicon-o-currency-dollar')
                ->url($url('chart-stats-cost'))
                ->isActiveWhen(fn () => request()->query('report') === 'chart-stats-cost'),
        ];
    }

    protected function getViewData(): array
    {
        $request = request();

        $period   = $request->query('period', 'this_month');
        $currency = $request->query('currency', 'ALL');
        $report   = $request->query('report', 'invoices');

        [$startDate, $endDate, $periodLabel] = $this->resolveDateRange($period);

        $currencyOptions = Currency::query()
            ->orderBy('code')
            ->pluck('code', 'code')
            ->toArray();

        $data = [
            'selectedPeriod'   => $period,
            'selectedCurrency' => $currency,
            'selectedReport'   => $report,
            'periodLabel'      => $periodLabel,
            'currencyOptions'  => $currencyOptions,
        ];

        if (in_array($report, ['chart-stats-count', 'chart-stats-cost'], true)) {
            $data = array_merge($data, $this->loadChartStatsReport($currency, $startDate, $endDate));
        }

        return $data;
    }

    private function loadInvoiceReport(string $currency, $startDate, $endDate): array
    {
        $query = Invoice::query()
            ->with(['purchaseOrder.contract', 'supplier']);

        if ($currency !== 'ALL') {
            $query->where('currency', $currency);
        }
        if ($startDate && $endDate) {
            $query->whereBetween('invoice_date', [$startDate, $endDate]);
        }

        $invoices = $query->orderByDesc('invoice_date')->limit(500)->get();

        return [
            'invoices'       => $invoices,
            'invoiceSummary' => [
                'count'    => $invoices->count(),
                'subtotal' => (float) $invoices->sum('subtotal'),
                'tax'      => (float) $invoices->sum('tax_amount'),
                'total'    => (float) $invoices->sum('total_amount'),
            ],
        ];
    }

    private function loadPurchaseOrderReport(string $currency, $startDate, $endDate): array
    {
        $query = PurchaseOrder::query()->with(['supplier', 'requisition']);

        if ($currency !== 'ALL') {
            $query->where('currency', $currency);
        }
        if ($startDate && $endDate) {
            $query->whereBetween('order_date', [$startDate, $endDate]);
        }

        $purchaseOrders = $query->orderByDesc('order_date')->limit(500)->get();

        return [
            'purchaseOrders' => $purchaseOrders,
            'poSummary'      => [
                'count' => $purchaseOrders->count(),
                'total' => (float) $purchaseOrders->sum('total_amount'),
            ],
        ];
    }

    private function loadLineItemsReport(string $currency, $startDate, $endDate): array
    {
        $query = PurchaseOrderItem::query()->with('purchaseOrder');
        $query->whereHas('purchaseOrder', function ($q) use ($currency, $startDate, $endDate) {
            if ($currency !== 'ALL') {
                $q->where('currency', $currency);
            }
            if ($startDate && $endDate) {
                $q->whereBetween('order_date', [$startDate, $endDate]);
            }
        });

        $items = $query->orderByDesc('id')->limit(1000)->get();

        return [
            'items'        => $items,
            'itemsSummary' => [
                'lines'     => $items->count(),
                'quantity'  => (float) $items->sum('quantity'),
                'lineTotal' => (float) $items->sum('line_total'),
            ],
        ];
    }

    private function loadCostByItemReport(string $currency, $startDate, $endDate): array
    {
        $query = PurchaseOrderItem::query()->with('purchaseOrder');
        $query->whereHas('purchaseOrder', function ($q) use ($currency, $startDate, $endDate) {
            if ($currency !== 'ALL') {
                $q->where('currency', $currency);
            }
            if ($startDate && $endDate) {
                $q->whereBetween('order_date', [$startDate, $endDate]);
            }
        });

        $items = $query->get();
        $costByItem = $items->groupBy('description')->map(function (Collection $group) {
            return [
                'description' => $group->first()->description,
                'unit'        => $group->first()->unit,
                'lines'       => $group->count(),
                'quantity'    => (float) $group->sum('quantity'),
                'total'       => (float) $group->sum('line_total'),
            ];
        })->sortByDesc('total')->values();

        return ['costByItem' => $costByItem];
    }

    private function loadPaymentsReport(string $currency, $startDate, $endDate): array
    {
        $query = Payment::query()->with(['invoice', 'supplier']);

        if ($currency !== 'ALL') {
            $query->where('currency', $currency);
        }
        if ($startDate && $endDate) {
            $query->whereBetween('payment_date', [$startDate, $endDate]);
        }

        $payments = $query->orderByDesc('payment_date')->limit(500)->get();

        return [
            'payments'        => $payments,
            'paymentsSummary' => [
                'count' => $payments->count(),
                'total' => (float) $payments->sum('amount'),
            ],
        ];
    }

    private function loadRequisitionsReport($startDate, $endDate): array
    {
        $query = Requisition::query()->with('requester');

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $requisitions = $query->orderByDesc('created_at')->limit(500)->get();

        return [
            'requisitions' => $requisitions,
            'reqSummary'   => [
                'count' => $requisitions->count(),
                'total' => (float) $requisitions->sum('estimated_total'),
            ],
        ];
    }

    private function loadChartStatsReport(string $currency, $startDate, $endDate): array
    {
        $query = PurchaseOrder::query();

        if ($currency !== 'ALL') {
            $query->where('currency', $currency);
        }
        if ($startDate && $endDate) {
            $query->whereBetween('order_date', [$startDate, $endDate]);
        }

        return [
            'chartStats' => [
                'poCount' => (clone $query)->count(),
                'poValue' => (float) (clone $query)->sum('total_amount'),
            ],
        ];
    }

    private function resolveDateRange(string $period): array
    {
        $today = now()->startOfDay();

        return match ($period) {
            'last_month' => [
                $today->copy()->subMonth()->startOfMonth(),
                $today->copy()->subMonth()->endOfMonth(),
                'Last Month',
            ],
            'this_year' => [
                $today->copy()->startOfYear(),
                $today->copy()->endOfYear(),
                'This Year',
            ],
            'last_year' => [
                $today->copy()->subYear()->startOfYear(),
                $today->copy()->subYear()->endOfYear(),
                'Last Year',
            ],
            'last_90_days' => [
                $today->copy()->subDays(89),
                $today->copy(),
                'Last 90 Days',
            ],
            'all_time' => [null, null, 'All Time'],
            default => [
                $today->copy()->startOfMonth(),
                $today->copy()->endOfMonth(),
                'This Month',
            ],
        };
    }
}

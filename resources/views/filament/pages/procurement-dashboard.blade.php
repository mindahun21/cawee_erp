<x-filament-panels::page>

    <div class="space-y-6">

        {{-- ══════════════════════════════════════════════════════════════ --}}
        {{-- HERO BANNER                                                     --}}
        {{-- ══════════════════════════════════════════════════════════════ --}}
        <div class="rounded-xl bg-white px-6 py-8 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white sm:text-3xl">Procurement Dashboard</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ now()->format('l, F j, Y') }} &nbsp;·&nbsp; FY {{ $currentYear }}
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    @php
                        $heroKpis = [
                            ['label' => 'Pending Requisitions', 'value' => $reqPending,     'icon' => 'heroicon-o-clipboard-document-list',   'cls' => $reqPending > 0    ? 'bg-warning-50 ring-warning-500/20 dark:bg-warning-500/10 dark:ring-warning-500/20' : 'bg-gray-50 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10'],
                            ['label' => 'Open Tenders',        'value' => $tendersOpen,     'icon' => 'heroicon-o-document-magnifying-glass', 'cls' => 'bg-gray-50 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10'],
                            ['label' => 'Overdue Invoices',    'value' => $invoiceOverdue,  'icon' => 'heroicon-o-exclamation-triangle',      'cls' => $invoiceOverdue > 0 ? 'bg-danger-50 ring-danger-500/20 dark:bg-danger-500/10 dark:ring-danger-500/20'   : 'bg-gray-50 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10'],
                            ['label' => 'GRNs Pending',        'value' => $grnPending,      'icon' => 'heroicon-o-truck',                     'cls' => $grnPending > 0    ? 'bg-info-50 ring-info-500/20 dark:bg-info-500/10 dark:ring-info-500/20'             : 'bg-gray-50 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10'],
                        ];
                    @endphp
                    @foreach($heroKpis as $kpi)
                        <div class="flex items-center gap-3 rounded-xl {{ $kpi['cls'] }} ring-1 ring-inset px-4 py-3">
                            <x-filament::icon :icon="$kpi['icon']" class="h-6 w-6 shrink-0 opacity-80 text-gray-400 dark:text-gray-500" />
                            <div>
                                <div class="text-xl font-bold leading-none text-gray-950 dark:text-white">{{ $kpi['value'] }}</div>
                                <div class="mt-0.5 text-[11px] font-medium uppercase tracking-wider opacity-70">{{ $kpi['label'] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════════ --}}
        {{-- SUMMARY STRIP                                                  --}}
        {{-- ══════════════════════════════════════════════════════════════ --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @php
                $strips = [
                    ['label' => 'PO Value YTD', 'value' => 'ETB ' . number_format($poValueYTD, 0), 'sub' => 'All purchase orders ' . $currentYear, 'icon' => 'heroicon-o-shopping-cart', 'iconBg' => 'bg-primary-50 dark:bg-primary-500/10', 'iconText' => 'text-primary-600 dark:text-primary-400', 'trend' => null],
                    ['label' => 'Budget Utilization', 'value' => $utilizationPct . '%', 'sub' => 'Committed + expended vs allocated', 'icon' => 'heroicon-o-chart-bar', 'iconBg' => $utilizationPct >= 90 ? 'bg-danger-50 dark:bg-danger-500/10' : ($utilizationPct >= 70 ? 'bg-warning-50 dark:bg-warning-500/10' : 'bg-success-50 dark:bg-success-500/10'), 'iconText' => $utilizationPct >= 90 ? 'text-danger-600 dark:text-danger-400' : ($utilizationPct >= 70 ? 'text-warning-600 dark:text-warning-400' : 'text-success-600 dark:text-success-400'), 'trend' => $utilizationPct >= 90 ? 'up' : null],
                    ['label' => 'Active Contracts', 'value' => $contractsActive, 'sub' => $contractsExpiring > 0 ? "{$contractsExpiring} expiring in 30 days ⚠️" : 'No contracts expiring soon', 'icon' => 'heroicon-o-document-check', 'iconBg' => $contractsExpiring > 0 ? 'bg-warning-50 dark:bg-warning-500/10' : 'bg-success-50 dark:bg-success-500/10', 'iconText' => $contractsExpiring > 0 ? 'text-warning-600 dark:text-warning-400' : 'text-success-600 dark:text-success-400', 'trend' => null],
                    ['label' => 'Payments Pending Auth.', 'value' => $paymentPending, 'sub' => 'ETB ' . number_format($paymentsThisMonth, 0) . ' processed this month', 'icon' => 'heroicon-o-banknotes', 'iconBg' => $paymentPending > 0 ? 'bg-warning-50 dark:bg-warning-500/10' : 'bg-success-50 dark:bg-success-500/10', 'iconText' => $paymentPending > 0 ? 'text-warning-600 dark:text-warning-400' : 'text-success-600 dark:text-success-400', 'trend' => null],
                ];
            @endphp
            @foreach($strips as $s)
                <div class="flex items-center gap-4 rounded-xl bg-white px-5 py-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl {{ $s['iconBg'] }}">
                        <x-filament::icon :icon="$s['icon']" class="h-6 w-6 {{ $s['iconText'] }}" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-baseline gap-1.5">
                            <span class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">{{ $s['value'] }}</span>
                            @if($s['trend'] === 'up')
                                <x-filament::icon icon="heroicon-s-arrow-trending-up" class="h-4 w-4 text-danger-500" />
                            @endif
                        </div>
                        <div class="text-sm font-semibold text-gray-950 dark:text-white leading-tight">{{ $s['label'] }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $s['sub'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- ══════════════════════════════════════════════════════════════ --}}
        {{-- WIDGETS GRID (Charts & Tables)                                 --}}
        {{-- ══════════════════════════════════════════════════════════════ --}}
        <x-filament-widgets::widgets
            :widgets="$this->getWidgets()"
            :columns="$this->getColumns()"
        />

    </div>

</x-filament-panels::page>

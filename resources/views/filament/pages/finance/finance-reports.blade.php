<x-filament-panels::page>
    <div class="space-y-6">

        @php
            $activeReport = request()->query('report');
            $basePath     = url()->current();
            $reportUrl    = fn(string $key) => $basePath . '?' . http_build_query([
                'report'   => $key,
                'period'   => request()->query('period', 'this_month'),
                'currency' => request()->query('currency', 'ALL'),
            ]);

            $categories = [
                'Business Overview' => [
                    ['key' => 'balance-sheet',       'label' => 'Balance Sheet',              'note' => 'Assets, Liabilities & Equity balances'],
                    ['key' => 'income-statement',    'label' => 'Income Statement (P&L)',      'note' => 'Revenue vs Expenditure — net surplus or deficit'],
                    ['key' => 'cash-flow',           'label' => 'Cash Flow Statement',         'note' => 'Operating, Investing & Financing cash flows'],
                    ['key' => 'donor-fund-summary',  'label' => 'Donor Fund Summary',          'note' => 'Grants received, spent & remaining per donor'],
                    ['key' => 'project-summary',     'label' => 'Project Financial Summary',   'note' => 'Budget vs actual per project with utilization'],
                    ['key' => 'budget-utilization',  'label' => 'Budget Utilization Detail',   'note' => 'Variance & utilization flags per budget line'],
                    ['key' => 'budget-vs-actual',    'label' => 'Budget vs. Actual',           'note' => 'Active budgets — budget amount vs actual spend'],
                    ['key' => 'aged-payables',       'label' => 'Aged Payables',               'note' => 'Outstanding payables in 0-30, 31-60, 61-90, 90+ day buckets'],
                ],
                'Bookkeeping' => [
                    ['key' => 'journal-entries',      'label' => 'Journal Entries',             'note' => 'All journal entries for the selected period'],
                    ['key' => 'gl-ledger',            'label' => 'General Ledger',              'note' => 'Raw GL postings with running balances'],
                    ['key' => 'account-statement',    'label' => 'Account Statement',           'note' => 'Transaction history per GL account'],
                    ['key' => 'trial-balance',        'label' => 'Trial Balance',               'note' => 'Posted GL balances by account'],
                    ['key' => 'payment-vouchers',     'label' => 'Payment Vouchers',             'note' => 'Payment vouchers by period and currency'],
                    ['key' => 'payment-requisitions', 'label' => 'Payment Requisitions',        'note' => 'Payment requisitions by period and currency'],
                    ['key' => 'bank-reconciliation',  'label' => 'Bank Reconciliation Summary', 'note' => 'Bank statement vs GL balance reconciliations'],
                ],
                'Tax & Compliance' => [
                    ['key' => 'wht-report', 'label' => 'Withholding Tax (WHT) Report', 'note' => 'Total gross, WHT withheld, and net paid per vendor'],
                ],
            ];
        @endphp

        @if (!$activeReport)
            {{-- ══════════════════════════════════════════
                 REPORT INDEX — categorized card grid
            ══════════════════════════════════════════ --}}

            @foreach($categories as $categoryName => $reports)
            <div class="rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-white/5 shadow-sm overflow-hidden">
                {{-- Category header --}}
                <div class="border-b border-gray-100 dark:border-white/10 bg-gray-50 dark:bg-white/5 px-5 py-3">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 tracking-wide">
                        {{ $categoryName }}
                    </h3>
                </div>

                {{-- Report links grid --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 divide-y divide-gray-100 dark:divide-white/10 sm:divide-y-0">
                    @foreach($reports as $index => $rep)
                        <a href="{{ $reportUrl($rep['key']) }}"
                           class="group flex flex-col gap-0.5 px-5 py-3 hover:bg-primary-50 dark:hover:bg-primary-500/10 transition-colors duration-150
                                  {{ ($index % 2 === 1 && count($reports) > 1) ? 'sm:border-l sm:border-gray-100 sm:dark:border-white/10' : '' }}
                                  {{ $index >= 2 ? 'sm:border-t sm:border-gray-100 sm:dark:border-white/10' : '' }}
                           ">
                            <span class="text-sm font-medium text-primary-600 dark:text-primary-400 group-hover:underline">
                                {{ $rep['label'] }}
                            </span>
                            <span class="text-xs text-gray-500 dark:text-gray-400 leading-snug">
                                {{ $rep['note'] }}
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>
            @endforeach

        @else
            {{-- ══════════════════════════════════════════
                 REPORT VIEW — filter bar + table
            ══════════════════════════════════════════ --}}

            {{-- Back + active report breadcrumb --}}
            <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                <a href="{{ $basePath }}"
                   class="flex items-center gap-1 font-medium text-primary-600 dark:text-primary-400 hover:underline">
                    <x-filament::icon icon="heroicon-o-arrow-left" class="h-4 w-4" />
                    Back To Report List
                </a>
                <span class="text-gray-300 dark:text-gray-600">/</span>
                <span class="font-semibold text-gray-700 dark:text-gray-200">
                    {{ match($activeReport) {
                        'balance-sheet'       => 'Balance Sheet',
                        'income-statement'    => 'Income Statement (P&L)',
                        'cash-flow'           => 'Cash Flow Statement',
                        'donor-fund-summary'  => 'Donor Fund Summary',
                        'project-summary'     => 'Project Financial Summary',
                        'budget-utilization'  => 'Budget Utilization Detail',
                        'budget-vs-actual'    => 'Budget vs. Actual',
                        'aged-payables'       => 'Aged Payables',
                        'journal-entries'     => 'Journal Entries',
                        'gl-ledger'           => 'General Ledger',
                        'account-statement'   => 'Account Statement',
                        'trial-balance'       => 'Trial Balance',
                        'payment-vouchers'    => 'Payment Vouchers',
                        'payment-requisitions'=> 'Payment Requisitions',
                        'bank-reconciliation' => 'Bank Reconciliation Summary',
                        'wht-report'          => 'Withholding Tax (WHT) Report',
                        default               => ucwords(str_replace('-', ' ', $activeReport)),
                    } }}
                </span>
            </div>

            {{-- Filter Bar --}}
            <div class="rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-white/5 shadow-sm overflow-hidden">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 dark:border-white/10 px-4 py-3 sm:px-6">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Filters</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Showing <strong class="text-gray-700 dark:text-gray-300">{{ $periodLabel ?? 'This Month' }}</strong>
                            @if(($currency ?? 'ALL') !== 'ALL')
                                &mdash; currency <strong class="text-gray-700 dark:text-gray-300">{{ $currency }}</strong>
                            @else
                                &mdash; all currencies
                            @endif
                        </p>
                    </div>
                </div>

                <div class="p-4 sm:p-6">
                    <form method="GET" class="flex flex-wrap items-end gap-4">
                        {{-- Preserve report param --}}
                        <input type="hidden" name="report" value="{{ $activeReport }}">

                        {{-- Date Filter Type --}}
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">Date Filter Type</label>
                            <x-filament::input.wrapper>
                                <x-filament::input.select name="date_filter_type" class="min-w-[160px]">
                                    <option value="period"    @selected(($dateFilterType ?? 'period')    === 'period')>By Period</option>
                                    <option value="date_range" @selected(($dateFilterType ?? '') === 'date_range')>Date Range</option>
                                    <option value="to_date"    @selected(($dateFilterType ?? '') === 'to_date')>To Date</option>
                                </x-filament::input.select>
                            </x-filament::input.wrapper>
                        </div>

                        {{-- Period --}}
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">Period</label>
                            <x-filament::input.wrapper>
                                <x-filament::input.select name="period" class="min-w-[160px]">
                                    <option value="this_month"   @selected(($period ?? 'this_month') === 'this_month')>This Month</option>
                                    <option value="last_month"   @selected(($period ?? '') === 'last_month')>Last Month</option>
                                    <option value="last_90_days" @selected(($period ?? '') === 'last_90_days')>Last 90 Days</option>
                                    <option value="this_year"    @selected(($period ?? '') === 'this_year')>This Year</option>
                                    <option value="last_year"    @selected(($period ?? '') === 'last_year')>Last Year</option>
                                    <option value="all_time"     @selected(($period ?? '') === 'all_time')>All Time</option>
                                </x-filament::input.select>
                            </x-filament::input.wrapper>
                        </div>

                        {{-- Accounting Period (for specific reports) --}}
                        @if(in_array($activeReport, ['trial-balance', 'gl-ledger', 'journal-entries', 'bank-reconciliation', 'balance-sheet', 'income-statement', 'cash-flow']))
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">Accounting Period</label>
                            <x-filament::input.wrapper>
                                <x-filament::input.select name="period_id" class="min-w-[200px]">
                                    <option value="">— All Periods —</option>
                                    @foreach($accountingPeriods as $pid => $pname)
                                        <option value="{{ $pid }}" @selected(($periodId ?? '') == $pid)>{{ $pname }}</option>
                                    @endforeach
                                </x-filament::input.select>
                            </x-filament::input.wrapper>
                        </div>
                        @endif

                        {{-- Accounting Method --}}
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">Accounting Method</label>
                            <x-filament::input.wrapper>
                                <x-filament::input.select name="accounting_method" class="min-w-[160px]">
                                    <option value="accrual" @selected(($accountingMethod ?? 'accrual') === 'accrual')>Accrual</option>
                                    <option value="cash"    @selected(($accountingMethod ?? '') === 'cash')>Cash</option>
                                </x-filament::input.select>
                            </x-filament::input.wrapper>
                        </div>

                        {{-- Currency (hidden for certain reports) --}}
                        @if(!in_array($activeReport, ['trial-balance', 'budget-vs-actual', 'bank-reconciliation', 'balance-sheet', 'income-statement']))
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">Currency</label>
                            <x-filament::input.wrapper>
                                <x-filament::input.select name="currency" class="min-w-[140px]">
                                    <option value="ALL" @selected(($currency ?? 'ALL') === 'ALL')>All</option>
                                    @foreach($currencyOptions as $code => $label)
                                        <option value="{{ $code }}" @selected(($currency ?? 'ALL') === $code)>{{ $code }}</option>
                                    @endforeach
                                </x-filament::input.select>
                            </x-filament::input.wrapper>
                        </div>
                        @endif

                        <x-filament::button type="submit" class="min-w-[120px] justify-center">
                            Filter
                        </x-filament::button>
                    </form>
                </div>
            </div>

            {{-- KPI Summary Strip --}}
            @if(in_array($activeReport, ['journal-entries', 'payment-vouchers', 'payment-requisitions', 'gl-ledger']))
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                @php
                    $rows = $this->getTableQuery()->get();
                    $kpis = match($activeReport) {
                        'journal-entries' => [
                            ['label' => 'Total Entries',    'value' => number_format($rows->count()),                                              'icon' => 'heroicon-o-document-duplicate', 'color' => 'text-primary-600 dark:text-primary-400'],
                            ['label' => 'Posted',           'value' => number_format($rows->where('status','posted')->count()),                    'icon' => 'heroicon-o-check-badge',        'color' => 'text-emerald-600 dark:text-emerald-400'],
                            ['label' => 'Pending Approval', 'value' => number_format($rows->where('status','pending_approval')->count()),           'icon' => 'heroicon-o-clock',              'color' => 'text-amber-600 dark:text-amber-400'],
                            ['label' => 'Total DR (ETB)',   'value' => 'ETB ' . number_format((float)$rows->sum(fn($r) => $r->lines()->sum('debit')), 2), 'icon' => 'heroicon-o-banknotes', 'color' => 'text-emerald-600 dark:text-emerald-400'],
                        ],
                        'payment-vouchers' => [
                            ['label' => 'Total PVs',    'value' => number_format($rows->count()),                              'icon' => 'heroicon-o-document-text', 'color' => 'text-primary-600 dark:text-primary-400'],
                            ['label' => 'Posted',       'value' => number_format($rows->where('status','posted')->count()),    'icon' => 'heroicon-o-check-badge',   'color' => 'text-emerald-600 dark:text-emerald-400'],
                            ['label' => 'Gross Amount', 'value' => 'ETB ' . number_format((float)$rows->sum('gross_amount'), 2), 'icon' => 'heroicon-o-calculator',  'color' => 'text-blue-600 dark:text-blue-400'],
                            ['label' => 'Net Amount',   'value' => 'ETB ' . number_format((float)$rows->sum('net_amount'), 2),   'icon' => 'heroicon-o-banknotes',   'color' => 'text-emerald-600 dark:text-emerald-400'],
                        ],
                        'payment-requisitions' => [
                            ['label' => 'Total PRs',    'value' => number_format($rows->count()),                               'icon' => 'heroicon-o-clipboard-document','color' => 'text-primary-600 dark:text-primary-400'],
                            ['label' => 'Approved',     'value' => number_format($rows->where('status','approved')->count()),   'icon' => 'heroicon-o-check-circle',     'color' => 'text-emerald-600 dark:text-emerald-400'],
                            ['label' => 'Gross Amount', 'value' => 'ETB ' . number_format((float)$rows->sum('total_amount'), 2), 'icon' => 'heroicon-o-calculator',      'color' => 'text-blue-600 dark:text-blue-400'],
                            ['label' => 'Net Payable',  'value' => 'ETB ' . number_format((float)$rows->sum('net_payable'), 2),  'icon' => 'heroicon-o-banknotes',       'color' => 'text-emerald-600 dark:text-emerald-400'],
                        ],
                        'gl-ledger' => [
                            ['label' => 'GL Lines',     'value' => number_format($rows->count()),                                                                              'icon' => 'heroicon-o-list-bullet',         'color' => 'text-primary-600 dark:text-primary-400'],
                            ['label' => 'Total Debit',  'value' => 'ETB ' . number_format((float)$rows->sum('debit'), 2),                                                     'icon' => 'heroicon-o-arrow-trending-up',   'color' => 'text-emerald-600 dark:text-emerald-400'],
                            ['label' => 'Total Credit', 'value' => 'ETB ' . number_format((float)$rows->sum('credit'), 2),                                                    'icon' => 'heroicon-o-arrow-trending-down', 'color' => 'text-red-600 dark:text-red-400'],
                            ['label' => 'Net Movement', 'value' => 'ETB ' . number_format(abs((float)$rows->sum('debit') - (float)$rows->sum('credit')), 2),                  'icon' => 'heroicon-o-scale',               'color' => 'text-gray-600 dark:text-gray-400'],
                        ],
                        default => [],
                    };
                @endphp
                @foreach($kpis as $kpi)
                <div class="rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-white/5 p-4 shadow-sm">
                    <div class="flex items-start gap-3">
                        <div class="shrink-0 rounded-lg bg-gray-100 dark:bg-white/10 p-2">
                            <x-filament::icon :icon="$kpi['icon']" class="h-5 w-5 text-gray-500 dark:text-gray-400" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 truncate">{{ $kpi['label'] }}</p>
                            <p class="mt-0.5 text-lg font-bold font-mono {{ $kpi['color'] }} truncate">{{ $kpi['value'] }}</p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            {{-- Data Table --}}
            {{ $this->table }}

        @endif
    </div>
</x-filament-panels::page>

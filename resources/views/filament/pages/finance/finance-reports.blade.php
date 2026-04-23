<x-filament-panels::page>
    <div class="space-y-6">

        {{-- ── Filter Bar ─────────────────────────────────────────────────── --}}
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
                    {{-- Report selector --}}
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">Report</label>
                        <x-filament::input.wrapper>
                            <x-filament::input.select name="report" class="min-w-[240px]">
                                <option value="journal-entries"      @selected(($report ?? 'journal-entries') === 'journal-entries')>Journal Entries</option>
                                <option value="payment-vouchers"     @selected(($report ?? '') === 'payment-vouchers')>Payment Vouchers</option>
                                <option value="payment-requisitions" @selected(($report ?? '') === 'payment-requisitions')>Payment Requisitions</option>
                                <option value="trial-balance"        @selected(($report ?? '') === 'trial-balance')>Trial Balance</option>
                                <option value="budget-vs-actual"     @selected(($report ?? '') === 'budget-vs-actual')>Budget vs. Actual</option>
                                <option value="gl-ledger"            @selected(($report ?? '') === 'gl-ledger')>General Ledger</option>
                                <option value="bank-reconciliation"  @selected(($report ?? '') === 'bank-reconciliation')>Bank Reconciliation</option>
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

                    {{-- Accounting Period (for Trial Balance / GL / Bank Reconciliation) --}}
                    @if(in_array(($report ?? 'journal-entries'), ['trial-balance', 'gl-ledger', 'journal-entries', 'bank-reconciliation']))
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

                    {{-- Currency (hidden for trial-balance, budget-vs-actual, bank-reconciliation) --}}
                    @if(!in_array(($report ?? ''), ['trial-balance', 'budget-vs-actual', 'bank-reconciliation']))
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

                    <x-filament::button type="submit" class="min-w-[160px] justify-center">
                        Apply Filters
                    </x-filament::button>
                </form>
            </div>
        </div>

        {{-- ── Summary KPI Strip ───────────────────────────────────────────── --}}
        @if(in_array(($report ?? 'journal-entries'), ['journal-entries', 'payment-vouchers', 'payment-requisitions', 'gl-ledger']))
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            @php
                $rows = $this->getTableQuery()->get();

                $kpis = match($report ?? 'journal-entries') {
                    'journal-entries' => [
                        ['label' => 'Total Entries',   'value' => number_format($rows->count()),                                         'icon' => 'heroicon-o-document-duplicate', 'color' => 'text-primary-600 dark:text-primary-400'],
                        ['label' => 'Posted',          'value' => number_format($rows->where('status','posted')->count()),               'icon' => 'heroicon-o-check-badge',        'color' => 'text-emerald-600 dark:text-emerald-400'],
                        ['label' => 'Pending Approval','value' => number_format($rows->where('status','pending_approval')->count()),      'icon' => 'heroicon-o-clock',              'color' => 'text-amber-600 dark:text-amber-400'],
                        ['label' => 'Total DR (ETB)',  'value' => 'ETB ' . number_format((float)$rows->sum(fn($r) => $r->lines()->sum('debit')), 2), 'icon' => 'heroicon-o-banknotes', 'color' => 'text-emerald-600 dark:text-emerald-400'],
                    ],
                    'payment-vouchers' => [
                        ['label' => 'Total PVs',    'value' => number_format($rows->count()),                                         'icon' => 'heroicon-o-document-text',  'color' => 'text-primary-600 dark:text-primary-400'],
                        ['label' => 'Posted',       'value' => number_format($rows->where('status','posted')->count()),               'icon' => 'heroicon-o-check-badge',    'color' => 'text-emerald-600 dark:text-emerald-400'],
                        ['label' => 'Gross Amount', 'value' => 'ETB ' . number_format((float)$rows->sum('gross_amount'), 2),         'icon' => 'heroicon-o-calculator',     'color' => 'text-blue-600 dark:text-blue-400'],
                        ['label' => 'Net Amount',   'value' => 'ETB ' . number_format((float)$rows->sum('net_amount'), 2),           'icon' => 'heroicon-o-banknotes',      'color' => 'text-emerald-600 dark:text-emerald-400'],
                    ],
                    'payment-requisitions' => [
                        ['label' => 'Total PRs',    'value' => number_format($rows->count()),                                         'icon' => 'heroicon-o-clipboard-document','color' => 'text-primary-600 dark:text-primary-400'],
                        ['label' => 'Approved',     'value' => number_format($rows->where('status','approved')->count()),             'icon' => 'heroicon-o-check-circle',   'color' => 'text-emerald-600 dark:text-emerald-400'],
                        ['label' => 'Gross Amount', 'value' => 'ETB ' . number_format((float)$rows->sum('total_amount'), 2),         'icon' => 'heroicon-o-calculator',     'color' => 'text-blue-600 dark:text-blue-400'],
                        ['label' => 'Net Payable',  'value' => 'ETB ' . number_format((float)$rows->sum('net_payable'), 2),          'icon' => 'heroicon-o-banknotes',      'color' => 'text-emerald-600 dark:text-emerald-400'],
                    ],
                    'gl-ledger' => [
                        ['label' => 'GL Lines',        'value' => number_format($rows->count()),                                          'icon' => 'heroicon-o-list-bullet',        'color' => 'text-primary-600 dark:text-primary-400'],
                        ['label' => 'Total Debit',     'value' => 'ETB ' . number_format((float)$rows->sum('debit'), 2),                 'icon' => 'heroicon-o-arrow-trending-up',  'color' => 'text-emerald-600 dark:text-emerald-400'],
                        ['label' => 'Total Credit',    'value' => 'ETB ' . number_format((float)$rows->sum('credit'), 2),               'icon' => 'heroicon-o-arrow-trending-down','color' => 'text-red-600 dark:text-red-400'],
                        ['label' => 'Net Movement',    'value' => 'ETB ' . number_format(abs((float)$rows->sum('debit') - (float)$rows->sum('credit')), 2), 'icon' => 'heroicon-o-scale', 'color' => 'text-gray-600 dark:text-gray-400'],
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

        {{-- ── Data Table ──────────────────────────────────────────────────── --}}
        {{ $this->table }}

    </div>
</x-filament-panels::page>

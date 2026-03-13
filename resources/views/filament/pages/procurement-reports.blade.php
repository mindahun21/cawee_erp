<x-filament-panels::page>
    {{-- Content area: filters + selected report (left sidebar provided by Filament getSubNavigation) --}}
    <div class="space-y-6">
            {{-- Filters --}}
            <div class="rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-white/5 shadow-sm overflow-hidden">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 dark:border-white/10 px-4 py-3 sm:px-6">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Filters</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Showing data for <strong class="text-gray-700 dark:text-gray-300">{{ $periodLabel ?? 'This Month' }}</strong>
                            @if(($selectedCurrency ?? 'ALL') !== 'ALL')
                                — currency <strong class="text-gray-700 dark:text-gray-300">{{ $selectedCurrency }}</strong>
                            @else
                                — all currencies
                            @endif
                        </p>
                    </div>
                </div>
                <div class="p-4 sm:p-6">
                    <form method="GET" class="flex flex-wrap items-end gap-4">
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">Report</label>
                            <x-filament::input.wrapper>
                                <x-filament::input.select name="report" class="min-w-[220px]">
                                    <option value="invoices" @selected(($selectedReport ?? 'invoices') === 'invoices')>Purchase Invoices Report</option>
                                    <option value="purchase-orders" @selected(($selectedReport ?? '') === 'purchase-orders')>Purchase Order Report</option>
                                    <option value="line-items" @selected(($selectedReport ?? '') === 'line-items')>Purchase Order Line Items</option>
                                    <option value="cost-by-item" @selected(($selectedReport ?? '') === 'cost-by-item')>Cost by Item</option>
                                    <option value="payments" @selected(($selectedReport ?? '') === 'payments')>Payments Report</option>
                                    <option value="requisitions" @selected(($selectedReport ?? '') === 'requisitions')>Requisitions Report</option>
                                    <option value="chart-stats-count" @selected(($selectedReport ?? '') === 'chart-stats-count')>Statistics — PO Count</option>
                                    <option value="chart-stats-cost" @selected(($selectedReport ?? '') === 'chart-stats-cost')>Statistics — PO Cost</option>
                                </x-filament::input.select>
                            </x-filament::input.wrapper>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">Currency</label>
                            <x-filament::input.wrapper>
                                <x-filament::input.select name="currency" class="min-w-[140px]">
                                    <option value="ALL" @selected(($selectedCurrency ?? 'ALL') === 'ALL')>All</option>
                                    @foreach($currencyOptions as $code => $label)
                                        <option value="{{ $code }}" @selected(($selectedCurrency ?? 'ALL') === $code)>{{ $code }}</option>
                                    @endforeach
                                </x-filament::input.select>
                            </x-filament::input.wrapper>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">Period</label>
                            <x-filament::input.wrapper>
                                <x-filament::input.select name="period" class="min-w-[160px]">
                                    <option value="this_month" @selected(($selectedPeriod ?? 'this_month') === 'this_month')>This Month</option>
                                    <option value="last_month" @selected(($selectedPeriod ?? '') === 'last_month')>Last Month</option>
                                    <option value="last_90_days" @selected(($selectedPeriod ?? '') === 'last_90_days')>Last 90 Days</option>
                                    <option value="this_year" @selected(($selectedPeriod ?? '') === 'this_year')>This Year</option>
                                    <option value="last_year" @selected(($selectedPeriod ?? '') === 'last_year')>Last Year</option>
                                    <option value="all_time" @selected(($selectedPeriod ?? '') === 'all_time')>All Time</option>
                                </x-filament::input.select>
                            </x-filament::input.wrapper>
                        </div>
                        <x-filament::button type="submit" class="min-w-[180px] justify-center">
                            Apply
                        </x-filament::button>
                    </form>
                </div>
            </div>

            {{-- Report content --}}
            @if(in_array(($selectedReport ?? 'invoices'), ['chart-stats-count', 'chart-stats-cost'], true))
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-filament::section>
                        <div class="space-y-1">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Purchase statistics</p>
                            <h4 class="text-base font-semibold text-gray-800 dark:text-gray-200">Purchase orders (count)</h4>
                        </div>
                        <div class="mt-4">
                            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($chartStats['poCount'] ?? 0) }}</p>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Purchase orders in selected period</p>
                        </div>
                    </x-filament::section>

                    <x-filament::section>
                        <div class="space-y-1">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Purchase statistics</p>
                            <h4 class="text-base font-semibold text-gray-800 dark:text-gray-200">Purchase orders (total cost)</h4>
                        </div>
                        <div class="mt-4">
                            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($chartStats['poValue'] ?? 0, 2) }}</p>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Total PO value in selected period</p>
                        </div>
                    </x-filament::section>
                </div>
            @else
                {{ $this->table }}
            @endif
    </div>
</x-filament-panels::page>

<x-filament-panels::page>
    @once
        <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    @endonce

    <div class="flex flex-col gap-6">
        {{-- Export Tools --}}
        <x-filament::section>
            <div class="flex flex-col sm:flex-row gap-4 items-center justify-between">
                <div>
                    <h2 class="text-base font-semibold leading-6 text-gray-950 dark:text-white">Export Tools</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Download the generated report in your preferred format.</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <x-filament::button color="danger" icon="heroicon-m-document-arrow-down" wire:click="export('pdf')">PDF Report</x-filament::button>
                    <x-filament::button color="success" icon="heroicon-m-table-cells" wire:click="export('excel')">Excel Sheet</x-filament::button>
                    <x-filament::button color="gray" icon="heroicon-m-list-bullet" wire:click="export('csv')">CSV File</x-filament::button>
                </div>
            </div>
        </x-filament::section>

        {{-- Horizontal Tabs --}}
        <x-filament::tabs label="Reports" class="w-full">
            <x-filament::tabs.item :active="$activeTab === 'summary'" wire:click="setActiveTab('summary')">
                Donation Summary
            </x-filament::tabs.item>
            <x-filament::tabs.item :active="$activeTab === 'geography'" wire:click="setActiveTab('geography')">
                Geography Report
            </x-filament::tabs.item>
            <x-filament::tabs.item :active="$activeTab === 'campaigns'" wire:click="setActiveTab('campaigns')">
                Campaign Performance
            </x-filament::tabs.item>
            <x-filament::tabs.item :active="$activeTab === 'trends'" wire:click="setActiveTab('trends')">
                Trends & Statistics
            </x-filament::tabs.item>
        </x-filament::tabs>

        {{-- Integrated Filters --}}
        <form wire:submit.prevent="loadReport" class="space-y-6">
            {{ $this->form }}
        </form>

        <div class="space-y-6">
            {{-- KPI Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="fi-wi-stats-overview-stat rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Funds Raised</h3>
                    <div class="mt-2 text-3xl font-semibold text-gray-950 dark:text-white">
                        {{ number_format($summary['total_amount'], 2) }}
                    </div>
                </div>
                <div class="fi-wi-stats-overview-stat rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Transactions</h3>
                    <div class="mt-2 text-3xl font-semibold text-primary-600 dark:text-primary-400">
                        {{ number_format($summary['count']) }}
                    </div>
                </div>
                <div class="fi-wi-stats-overview-stat rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Unique Donors</h3>
                    <div class="mt-2 text-3xl font-semibold text-success-600 dark:text-success-400">
                        {{ number_format($summary['donors']) }}
                    </div>
                </div>
            </div>

            {{-- Trends Chart --}}
            @if($activeTab === 'trends')
                <x-filament::section>
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Donation Trends</h3>
                    </div>
                    <div wire:key="chart-{{ md5(json_encode($chartData)) }}" class="w-full h-80">
                        <div x-data="{
                            init() {
                                let chart = new ApexCharts($el, {
                                    series: [{
                                        name: 'Amount',
                                        type: 'column',
                                        data: @js($chartData['amounts'] ?? [])
                                    }, {
                                        name: 'Count',
                                        type: 'line',
                                        data: @js($chartData['counts'] ?? [])
                                    }],
                                    chart: {
                                        height: 320,
                                        type: 'line',
                                        toolbar: { show: false },
                                        animations: { enabled: false }
                                    },
                                    stroke: { width: [0, 4] },
                                    colors: ['#f59e0b', '#3b82f6'],
                                    dataLabels: { enabled: true, enabledOnSeries: [1] },
                                    labels: @js($chartData['labels'] ?? []),
                                    xaxis: { type: 'category' },
                                    yaxis: [{ title: { text: 'Amount' } }, { opposite: true, title: { text: 'Count' } }],
                                    theme: { mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light' }
                                });
                                chart.render();
                            }
                        }"></div>
                    </div>
                </x-filament::section>
            @else
                {{-- Data Table --}}
                <div class="shadow-sm">
                    {{ $this->table }}
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>

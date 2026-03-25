<x-filament-panels::page>
    @once
        <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
        <style>
            .dark .apexcharts-tooltip {
                background: #111827 !important;
                border: 1px solid #374151 !important;
                color: #f9fafb !important;
                box-shadow: none !important;
            }
            .dark .apexcharts-tooltip-title {
                background: #1f2937 !important;
                border-bottom: 1px solid #374151 !important;
            }
        </style>
    @endonce

    <div class="flex flex-col gap-6">
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

        <x-filament::tabs label="Reports" class="w-full">
            @foreach([
                'valuation' => 'Asset Valuation',
                'depreciation' => 'Depreciation',
                'aging' => 'Asset Aging',
                'utilization' => 'Utilization',
                'movement' => 'Movement',
                'damaged' => 'Lost/Damaged',
                'location' => 'Location-wise',
                'category' => 'Category-wise',
                'turnover' => 'Turnover'
            ] as $key => $label)
                <x-filament::tabs.item :active="$activeTab === $key" wire:click="setActiveTab('{{ $key }}')">{{ $label }}</x-filament::tabs.item>
            @endforeach
        </x-filament::tabs>

        <form wire:submit="loadReport">
            {{ $this->form }}
        </form>

        <div class="space-y-6">
            @php $chartKey = "chart-{$activeTab}-" . md5(json_encode($reportData)); @endphp

            @if($activeTab === 'valuation')
                @php
                    $cost = $reportData['total_purchase_cost'] ?? 0;
                    $current = $reportData['current_market_value'] ?? 0;
                    $lost = max(0, $cost - $current);
                @endphp
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div class="col-span-1 flex flex-col gap-6">
                        <!-- Standard Filament Stat Cards -->
                        <div class="fi-wi-stats-overview-stat rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Purchase Cost</h3>
                            <div class="mt-2 text-3xl font-semibold text-gray-950 dark:text-white">Rs {{ number_format($cost) }}</div>
                        </div>
                        <div class="fi-wi-stats-overview-stat rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Current Market Value</h3>
                            <div class="mt-2 text-3xl font-semibold text-success-600 dark:text-success-400">Rs {{ number_format($current) }}</div>
                        </div>
                        <div class="fi-wi-stats-overview-stat rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Depreciation (Lost Value)</h3>
                            <div class="mt-2 text-3xl font-semibold text-danger-600 dark:text-danger-400">Rs {{ number_format($lost) }}</div>
                        </div>
                    </div>
                    <div class="col-span-1 md:col-span-2">
                        <x-filament::section class="h-full flex items-center justify-center">
                            <div wire:key="{{ $chartKey }}" class="w-full flex justify-center">
                                <div x-data x-init="
                                    new ApexCharts($el, {
                                        series: [{{ $current }}, {{ $lost }}],
                                        labels: ['Retained Value', 'Depreciated Value'],
                                        chart: { type: 'pie', height: 320, background: 'transparent', foreColor: 'inherit', animations: { enabled: false } },
                                        colors: ['#22c55e', '#ef4444'],
                                        theme: { mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light' },
                                        stroke: { show: false },
                                        legend: { position: 'bottom' }
                                    }).render();
                                "></div>
                            </div>
                        </x-filament::section>
                    </div>
                </div>

            @elseif($activeTab === 'depreciation')
                @if(count($reportData['top_5'] ?? []) > 0)
                <x-filament::section class="mb-6 shadow-sm">
                    <h3 class="text-lg font-bold mb-4 text-gray-900 dark:text-white">Highest Depreciating Assets</h3>
                    @php
                        $names = collect($reportData['top_5'])->pluck('name')->toJson();
                        $values = collect($reportData['top_5'])->pluck('monthly_depreciation')->toJson();
                    @endphp
                    <div wire:key="{{ $chartKey }}" class="w-full">
                        <div x-data x-init="
                            new ApexCharts($el, {
                                series: [{ name: 'Monthly Depreciation (Rs)', data: {{ $values }} }],
                                chart: { type: 'bar', height: 280, background: 'transparent', foreColor: 'inherit', toolbar: { show: false }, animations: { enabled: false } },
                                colors: ['#f97316'],
                                plotOptions: { bar: { horizontal: true, borderRadius: 4, dataLabels: { position: 'top' } } },
                                dataLabels: { enabled: true, offsetX: 20, style: { fontSize: '12px', colors: ['#9ca3af'] } },
                                xaxis: { categories: {!! $names !!} },
                                theme: { mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light' }
                            }).render();
                        "></div>
                    </div>
                </x-filament::section>
                @endif

            @elseif($activeTab === 'aging')
                @php
                    $new = $reportData['metrics']['new'] ?? 0;
                    $mid = $reportData['metrics']['mid'] ?? 0;
                    $eol = $reportData['metrics']['eol'] ?? 0;
                @endphp
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div class="col-span-1 flex flex-col gap-6">
                        <div class="fi-wi-stats-overview-stat rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">New (< 1 yr)</h3>
                            <div class="mt-2 text-3xl font-semibold text-success-600 dark:text-success-400">{{ $new }}</div>
                        </div>
                        <div class="fi-wi-stats-overview-stat rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Mid-Life (1-3 yrs)</h3>
                            <div class="mt-2 text-3xl font-semibold text-primary-600 dark:text-primary-400">{{ $mid }}</div>
                        </div>
                        <div class="fi-wi-stats-overview-stat rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Nearing EOL (> 3 yrs)</h3>
                            <div class="mt-2 text-3xl font-semibold text-danger-600 dark:text-danger-400">{{ $eol }}</div>
                        </div>
                    </div>
                    <div class="col-span-1 md:col-span-2">
                        <x-filament::section class="h-full flex items-center justify-center">
                            <div wire:key="{{ $chartKey }}" class="w-full flex justify-center">
                                <div x-data x-init="
                                    new ApexCharts($el, {
                                        series: [{{ $new }}, {{ $mid }}, {{ $eol }}],
                                        labels: ['New (< 1 yr)', 'Mid-Life (1-3 yrs)', 'Nearing EOL (> 3 yrs)'],
                                        chart: { type: 'donut', height: 320, background: 'transparent', foreColor: 'inherit', animations: { enabled: false } },
                                        colors: ['#22c55e', '#3b82f6', '#ef4444'],
                                        stroke: { show: false },
                                        theme: { mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light' },
                                        legend: { position: 'bottom' }
                                    }).render();
                                "></div>
                            </div>
                        </x-filament::section>
                    </div>
                </div>

            @elseif($activeTab === 'utilization')
                @php
                    $high = $reportData['metrics']['high'] ?? 0;
                    $mod = $reportData['metrics']['moderate'] ?? 0;
                    $idle = $reportData['metrics']['idle'] ?? 0;
                @endphp
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div class="col-span-1 flex flex-col gap-6">
                        <div class="fi-wi-stats-overview-stat rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 border-l-4 border-l-primary-500">
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Highly Utilized</h3>
                            <div class="mt-2 text-3xl font-semibold text-primary-600 dark:text-primary-400">{{ $high }}</div>
                        </div>
                        <div class="fi-wi-stats-overview-stat rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 border-l-4 border-l-warning-500">
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Moderately Utilized</h3>
                            <div class="mt-2 text-3xl font-semibold text-warning-600 dark:text-warning-400">{{ $mod }}</div>
                        </div>
                        <div class="fi-wi-stats-overview-stat rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 border-l-4 border-l-gray-400">
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Idle (0%)</h3>
                            <div class="mt-2 text-3xl font-semibold text-gray-500 dark:text-gray-400">{{ $idle }}</div>
                        </div>
                    </div>
                    <div class="col-span-1 md:col-span-2">
                        <x-filament::section class="h-full flex items-center justify-center">
                            <div wire:key="{{ $chartKey }}" class="w-full flex justify-center">
                                <div x-data x-init="
                                    new ApexCharts($el, {
                                        series: [{{ $high }}, {{ $mod }}, {{ $idle }}],
                                        labels: ['Highly Utilized', 'Moderately Utilized', 'Idle'],
                                        chart: { type: 'pie', height: 320, background: 'transparent', foreColor: 'inherit', animations: { enabled: false } },
                                        colors: ['#3b82f6', '#f59e0b', '#9ca3af'],
                                        stroke: { show: false },
                                        theme: { mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light' },
                                        legend: { position: 'bottom' }
                                    }).render();
                                "></div>
                            </div>
                        </x-filament::section>
                    </div>
                </div>

            @elseif($activeTab === 'movement')
                @php
                    $in = $reportData['metrics']['in'] ?? 0;
                    $out = $reportData['metrics']['out'] ?? 0;
                    $transfer = $reportData['metrics']['transfer'] ?? 0;
                @endphp
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div class="fi-wi-stats-overview-stat rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                        <div class="flex items-center gap-4">
                            <div class="p-3 bg-success-500/10 rounded-lg text-success-600 dark:bg-success-500/20 dark:text-success-400">
                                <x-filament::icon icon="heroicon-o-arrow-trending-up" class="w-6 h-6" />
                            </div>
                            <div>
                                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Stock In</h3>
                                <div class="mt-1 text-2xl font-semibold text-gray-950 dark:text-white">{{ $in }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="fi-wi-stats-overview-stat rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                        <div class="flex items-center gap-4">
                            <div class="p-3 bg-danger-500/10 rounded-lg text-danger-600 dark:bg-danger-500/20 dark:text-danger-400">
                                <x-filament::icon icon="heroicon-o-arrow-trending-down" class="w-6 h-6" />
                            </div>
                            <div>
                                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Stock Out</h3>
                                <div class="mt-1 text-2xl font-semibold text-gray-950 dark:text-white">{{ $out }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="fi-wi-stats-overview-stat rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                        <div class="flex items-center gap-4">
                            <div class="p-3 bg-info-500/10 rounded-lg text-info-600 dark:bg-info-500/20 dark:text-info-400">
                                <x-filament::icon icon="heroicon-o-arrows-right-left" class="w-6 h-6" />
                            </div>
                            <div>
                                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Transfers</h3>
                                <div class="mt-1 text-2xl font-semibold text-gray-950 dark:text-white">{{ $transfer }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <x-filament::section class="mb-6 shadow-sm">
                    <h3 class="text-lg font-bold mb-4 text-gray-900 dark:text-white">Movement Volume Graph</h3>
                    <div wire:key="{{ $chartKey }}" class="w-full">
                        <div x-data x-init="
                            new ApexCharts($el, {
                                series: [{ name: 'Volume', data: [{{ $in }}, {{ $out }}, {{ $transfer }}] }],
                                labels: ['Stock In', 'Stock Out', 'Transfers'],
                                chart: { type: 'bar', height: 250, background: 'transparent', foreColor: 'inherit', toolbar: { show: false }, animations: { enabled: false } },
                                plotOptions: { bar: { distributed: true, borderRadius: 4, horizontal: false } },
                                colors: ['#22c55e', '#ef4444', '#0ea5e9'],
                                dataLabels: { enabled: false },
                                xaxis: { categories: ['Stock In', 'Stock Out', 'Transfers'] },
                                legend: { show: false },
                                theme: { mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light' }
                            }).render();
                        "></div>
                    </div>
                </x-filament::section>

            @elseif($activeTab === 'damaged')
                @php
                    $lost = $reportData['metrics']['lost'] ?? 0;
                    $broken = $reportData['metrics']['broken'] ?? 0;
                    $poor = $reportData['metrics']['poor'] ?? 0;
                @endphp
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div class="col-span-1 flex flex-col gap-6">
                        <div class="fi-wi-stats-overview-stat rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Reported Lost</h3>
                            <div class="mt-2 text-3xl font-semibold text-gray-950 dark:text-white">{{ $lost }}</div>
                        </div>
                        <div class="fi-wi-stats-overview-stat rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Broken</h3>
                            <div class="mt-2 text-3xl font-semibold text-danger-600 dark:text-danger-400">{{ $broken }}</div>
                        </div>
                        <div class="fi-wi-stats-overview-stat rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Poor Condition</h3>
                            <div class="mt-2 text-3xl font-semibold text-warning-600 dark:text-warning-400">{{ $poor }}</div>
                        </div>
                    </div>
                    <div class="col-span-1 md:col-span-2">
                        <x-filament::section class="h-full flex items-center justify-center">
                            <div wire:key="{{ $chartKey }}" class="w-full flex justify-center">
                                <div x-data x-init="
                                    new ApexCharts($el, {
                                        series: [{{ $lost }}, {{ $broken }}, {{ $poor }}],
                                        labels: ['Lost', 'Broken', 'Poor Condition'],
                                        chart: { type: 'polarArea', height: 350, background: 'transparent', foreColor: 'inherit', animations: { enabled: false } },
                                        colors: ['#6b7280', '#ef4444', '#f59e0b'],
                                        stroke: { colors: ['transparent'] },
                                        fill: { opacity: 0.8 },
                                        theme: { mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light' },
                                        legend: { position: 'bottom' }
                                    }).render();
                                "></div>
                            </div>
                        </x-filament::section>
                    </div>
                </div>

            @elseif($activeTab === 'location')
                @if(count($reportData['locations'] ?? []) > 0)
                <x-filament::section class="mb-6 shadow-sm">
                    <h3 class="text-lg font-bold mb-4 text-gray-900 dark:text-white">Financial Value by Location</h3>
                    @php
                        $names = collect($reportData['locations'])->pluck('name')->toJson();
                        $values = collect($reportData['locations'])->pluck('total_value')->toJson();
                    @endphp
                    <div wire:key="{{ $chartKey }}" class="w-full">
                        <div x-data x-init="
                            new ApexCharts($el, {
                                series: [{ name: 'Financial Value (Rs)', data: {{ $values }} }],
                                chart: { type: 'bar', height: 350, background: 'transparent', foreColor: 'inherit', toolbar: { show: false }, animations: { enabled: false } },
                                colors: ['#0ea5e9'],
                                plotOptions: { bar: { borderRadius: 4, dataLabels: { position: 'top' } } },
                                dataLabels: { enabled: true, formatter: function(val) { return 'Rs ' + val.toLocaleString(); }, offsetY: -20, style: { fontSize: '12px', colors: ['#9ca3af'] } },
                                xaxis: { categories: {!! $names !!} },
                                theme: { mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light' }
                            }).render();
                        "></div>
                    </div>
                </x-filament::section>
                @endif

            @elseif($activeTab === 'category')
                @if(count($reportData['categories'] ?? []) > 0)
                <x-filament::section class="mb-6 shadow-sm">
                    <h3 class="text-lg font-bold mb-4 text-gray-900 dark:text-white">Assets by Category</h3>
                    @php
                        $names = collect($reportData['categories'])->pluck('name')->toJson();
                        $counts = collect($reportData['categories'])->pluck('asset_count')->toJson();
                    @endphp
                    <div wire:key="{{ $chartKey }}" class="w-full flex justify-center">
                        <div x-data x-init="
                            new ApexCharts($el, {
                                series: {{ $counts }},
                                labels: {!! $names !!},
                                chart: { type: 'pie', height: 380, background: 'transparent', foreColor: 'inherit', animations: { enabled: false } },
                                stroke: { show: false },
                                dataLabels: { enabled: true, formatter: function (val, opts) { return opts.w.config.series[opts.seriesIndex] + ' units'; } },
                                theme: { mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light', palette: 'palette1' },
                                legend: { position: 'bottom' }
                            }).render();
                        "></div>
                    </div>
                </x-filament::section>
                @endif

            @elseif($activeTab === 'turnover')
                @if(count($reportData['top_3'] ?? []) > 0)
                <x-filament::section class="mb-6 shadow-sm">
                    <h3 class="text-lg font-bold mb-4 text-gray-900 dark:text-white">Turnover Leaders</h3>
                    @php
                        $names = collect($reportData['top_3'])->pluck('name')->toJson();
                        $ratios = collect($reportData['top_3'])->pluck('turnover_ratio')->map(fn($v) => round($v, 2))->toJson();
                    @endphp
                    <div wire:key="{{ $chartKey }}" class="w-full">
                        <div x-data x-init="
                            new ApexCharts($el, {
                                series: [{ name: 'Turnover Ratio', data: {{ $ratios }} }],
                                chart: { type: 'line', height: 300, background: 'transparent', foreColor: 'inherit', toolbar: { show: false }, animations: { enabled: false } },
                                stroke: { width: 4, curve: 'smooth' },
                                colors: ['#f59e0b'],
                                markers: { size: 6 },
                                xaxis: { categories: {!! $names !!} },
                                theme: { mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light' }
                            }).render();
                        "></div>
                    </div>
                </x-filament::section>
                @endif
            @endif

            {{-- Standard Data Tables --}}
            @if($activeTab === 'valuation')
                <x-filament::section class="p-0">
                    <div class="overflow-x-auto -m-6">
                        <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                            <thead class="bg-gray-50 dark:bg-white/5 font-semibold text-gray-950 dark:text-white">
                                <tr>
                                    <th scope="col" class="px-6 py-4 rounded-tl-lg">Asset Name</th>
                                    <th scope="col" class="px-6 py-4">Code</th>
                                    <th scope="col" class="px-6 py-4 text-right">Purchase Cost</th>
                                    <th scope="col" class="px-6 py-4 text-right">Current Value</th>
                                    <th scope="col" class="px-6 py-4 text-center rounded-tr-lg">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                                @foreach($reportData['assets'] ?? [] as $asset)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition duration-75">
                                        <td class="px-6 py-4 font-medium text-gray-950 dark:text-white">{{ $asset['name'] }}</td>
                                        <td class="px-6 py-4">{{ $asset['code'] }}</td>
                                        <td class="px-6 py-4 text-right">{{ number_format($asset['cost'], 2) }}</td>
                                        <td class="px-6 py-4 text-right font-medium text-success-600 dark:text-success-400">{{ number_format($asset['current'], 2) }}</td>
                                        <td class="px-6 py-4 text-center">
                                            <x-filament::badge>{{ $asset['status'] ?? 'N/A' }}</x-filament::badge>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>

            @elseif($activeTab === 'depreciation')
                <x-filament::section class="p-0">
                    <div class="overflow-x-auto -m-6">
                        <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                            <thead class="bg-gray-50 dark:bg-white/5 font-semibold text-gray-950 dark:text-white">
                                <tr>
                                    <th scope="col" class="px-6 py-4">Period</th>
                                    <th scope="col" class="px-6 py-4">Asset Name</th>
                                    <th scope="col" class="px-6 py-4 text-right">Monthly Depr.</th>
                                    <th scope="col" class="px-6 py-4 text-right">Current Value</th>
                                    <th scope="col" class="px-6 py-4 text-right">Rem. Months</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                                @foreach($reportData['assets'] ?? [] as $row)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition duration-75">
                                        <td class="px-6 py-4">{{ $row['period'] }}</td>
                                        <td class="px-6 py-4 font-medium text-gray-950 dark:text-white">{{ $row['name'] }}</td>
                                        <td class="px-6 py-4 text-right text-danger-600 dark:text-danger-400">-{{ number_format($row['monthly_depreciation'], 2) }}</td>
                                        <td class="px-6 py-4 text-right font-medium text-primary-600 dark:text-primary-400">{{ number_format($row['current_value'], 2) }}</td>
                                        <td class="px-6 py-4 text-right">{{ $row['remaining_months'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>

            @elseif($activeTab === 'aging')
                <x-filament::section class="p-0">
                    <div class="overflow-x-auto -m-6">
                        <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                            <thead class="bg-gray-50 dark:bg-white/5 font-semibold text-gray-950 dark:text-white">
                                <tr>
                                    <th scope="col" class="px-6 py-4">Asset Name</th>
                                    <th scope="col" class="px-6 py-4">Purchase Date</th>
                                    <th scope="col" class="px-6 py-4 text-center">Age (Months)</th>
                                    <th scope="col" class="px-6 py-4 text-center">Remaining (Months)</th>
                                    <th scope="col" class="px-6 py-4 text-center">Useful Life</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                                @foreach($reportData['assets'] ?? [] as $row)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition duration-75">
                                        <td class="px-6 py-4 font-medium text-gray-950 dark:text-white">{{ $row['name'] }}</td>
                                        <td class="px-6 py-4">{{ $row['purchase_date']->format('d/m/Y') }}</td>
                                        <td class="px-6 py-4 text-center">{{ $row['age_months'] }}</td>
                                        <td class="px-6 py-4 text-center">
                                            <x-filament::badge :color="$row['remaining_months'] < 12 ? 'danger' : 'success'">
                                                {{ $row['remaining_months'] }}
                                            </x-filament::badge>
                                        </td>
                                        <td class="px-6 py-4 text-center text-gray-400">{{ $row['useful_life_months'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>

            @elseif($activeTab === 'utilization')
                <x-filament::section class="p-0">
                    <div class="overflow-x-auto -m-6">
                        <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                            <thead class="bg-gray-50 dark:bg-white/5 font-semibold text-gray-950 dark:text-white">
                                <tr>
                                    <th scope="col" class="px-6 py-4">Asset Name</th>
                                    <th scope="col" class="px-6 py-4 text-center">Total Stock</th>
                                    <th scope="col" class="px-6 py-4 text-center">Assigned</th>
                                    <th scope="col" class="px-6 py-4 text-center">Utilization</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                                @foreach($reportData['assets'] ?? [] as $row)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition duration-75">
                                        <td class="px-6 py-4 font-medium text-gray-950 dark:text-white">{{ $row['name'] }}</td>
                                        <td class="px-6 py-4 text-center">{{ $row['total_qty'] }}</td>
                                        <td class="px-6 py-4 text-center font-medium text-primary-600 dark:text-primary-400">{{ $row['assigned_qty'] }}</td>
                                        <td class="px-6 py-4 text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                <div class="w-24 bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 overflow-hidden">
                                                    <div class="bg-primary-600 h-1.5 rounded-full" style="width: {{ $row['utilization_rate'] }}%"></div>
                                                </div>
                                                <span class="text-xs font-semibold">{{ number_format($row['utilization_rate'], 1) }}%</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>

            @elseif($activeTab === 'movement')
                <x-filament::section class="p-0">
                    <div class="overflow-x-auto -m-6">
                        <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                            <thead class="bg-gray-50 dark:bg-white/5 font-semibold text-gray-950 dark:text-white">
                                <tr>
                                    <th scope="col" class="px-6 py-4">Date</th>
                                    <th scope="col" class="px-6 py-4">Asset</th>
                                    <th scope="col" class="px-6 py-4">Type</th>
                                    <th scope="col" class="px-6 py-4 text-center">Qty</th>
                                    <th scope="col" class="px-6 py-4">Origin</th>
                                    <th scope="col" class="px-6 py-4">Destination</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                                @foreach($reportData['movements'] ?? [] as $move)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition duration-75">
                                        <td class="px-6 py-4">{{ $move->date->format('d/m/Y') }}</td>
                                        <td class="px-6 py-4 font-medium text-primary-600 dark:text-primary-400">
                                            {{ $move->asset->name ?? '--' }}
                                        </td>
                                        <td class="px-6 py-4">
                                            <x-filament::badge :color="match($move->type) {
                                                'Stock In' => 'success',
                                                'Stock Out' => 'danger',
                                                'Transfer' => 'info',
                                                default => 'gray'
                                            }">{{ $move->type }}</x-filament::badge>
                                        </td>
                                        <td class="px-6 py-4 text-center font-semibold">{{ $move->quantity }}</td>
                                        <td class="px-6 py-4 text-xs">{{ $move->fromLocation->location_name ?? 'SYSTEM' }}</td>
                                        <td class="px-6 py-4 text-xs">{{ $move->toLocation->location_name ?? 'RECIPIENT' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>

            @elseif($activeTab === 'damaged')
                <x-filament::section class="p-0">
                    <div class="overflow-x-auto -m-6">
                        <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                            <thead class="bg-gray-50 dark:bg-white/5 font-semibold text-gray-950 dark:text-white">
                                <tr>
                                    <th scope="col" class="px-6 py-4">Asset Name</th>
                                    <th scope="col" class="px-6 py-4">Status</th>
                                    <th scope="col" class="px-6 py-4">Condition</th>
                                    <th scope="col" class="px-6 py-4">Location</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                                @forelse($reportData['assets'] ?? [] as $asset)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition duration-75">
                                        <td class="px-6 py-4 font-medium text-gray-950 dark:text-white">
                                            {{ $asset->name }} <br> <span class="text-xs text-gray-400 font-normal">{{ $asset->barcode }}</span>
                                        </td>
                                        <td class="px-6 py-4"><x-filament::badge color="danger">{{ $asset->statusRecord?->name ?? 'N/A' }}</x-filament::badge></td>
                                        <td class="px-6 py-4"><x-filament::badge color="warning">{{ $asset->condition?->name ?? 'N/A' }}</x-filament::badge></td>
                                        <td class="px-6 py-4">{{ $asset->location->location_name ?? 'N/A' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400 italic">No lost or damaged assets found in logs</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>

            @elseif($activeTab === 'location' || $activeTab === 'category')
                <x-filament::section class="p-0">
                    <div class="overflow-x-auto -m-6">
                        <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                            <thead class="bg-gray-50 dark:bg-white/5 font-semibold text-gray-950 dark:text-white">
                                <tr>
                                    <th scope="col" class="px-6 py-4">Grouping Name</th>
                                    <th scope="col" class="px-6 py-4 text-center">Asset Count</th>
                                    <th scope="col" class="px-6 py-4 text-right">Financial Value</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                                @php $tableRows = $activeTab === 'location' ? ($reportData['locations'] ?? []) : ($reportData['categories'] ?? []); @endphp
                                @foreach($tableRows as $row)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition duration-75">
                                        <td class="px-6 py-4 font-medium text-gray-950 dark:text-white">{{ $row['name'] }}</td>
                                        <td class="px-6 py-4 text-center font-semibold">{{ number_format($row['asset_count']) }}</td>
                                        <td class="px-6 py-4 text-right font-medium text-primary-600 dark:text-primary-400">Rs. {{ number_format($row['total_value'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>

            @elseif($activeTab === 'turnover')
                <x-filament::section class="p-0">
                    <div class="overflow-x-auto -m-6">
                        <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                            <thead class="bg-gray-50 dark:bg-white/5 font-semibold text-gray-950 dark:text-white">
                                <tr>
                                    <th scope="col" class="px-6 py-4">Asset Name</th>
                                    <th scope="col" class="px-6 py-4 text-center">Units Out</th>
                                    <th scope="col" class="px-6 py-4 text-center">Available Stock</th>
                                    <th scope="col" class="px-6 py-4 text-center">Turnover Ratio</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                                @foreach($reportData['assets'] ?? [] as $row)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition duration-75">
                                        <td class="px-6 py-4 font-medium text-gray-950 dark:text-white">{{ $row['name'] }}</td>
                                        <td class="px-6 py-4 text-center">{{ number_format($row['outgoing']) }}</td>
                                        <td class="px-6 py-4 text-center">{{ number_format($row['current_stock']) }}</td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="px-3 py-1 bg-primary-50 dark:bg-primary-500/10 text-primary-600 rounded-full font-semibold text-xs border border-primary-500/20">
                                                {{ number_format($row['turnover_ratio'], 2) }}x
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>
            @endif
        </div>
    </div>
</x-filament-panels::page>

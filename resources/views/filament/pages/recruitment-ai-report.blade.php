<x-filament-panels::page>
    <div class="space-y-6" x-data="aiReportApp()" x-cloak>

        {{-- ═══ Input Section ═══ --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center justify-between w-full">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-sparkles class="h-5 w-5 text-purple-500" />
                        <span>AI-Powered Recruitment Analysis</span>
                    </div>
                    <button
                        type="button"
                        wire:click="generate"
                        wire:loading.attr="disabled"
                        wire:target="generate"
                        class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-lg px-4 py-2 text-sm font-semibold shadow-sm outline-none transition duration-75 bg-primary-600 text-white hover:bg-primary-500 focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 dark:bg-primary-500 dark:hover:bg-primary-400 disabled:opacity-70 disabled:cursor-wait"
                    >
                        <span wire:loading.remove wire:target="generate" class="inline-flex items-center gap-1.5">
                            <x-heroicon-o-sparkles class="h-4 w-4" />
                            Generate Report
                        </span>
                        <span wire:loading.inline-flex wire:target="generate" class="items-center gap-1.5">
                            <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            Analyzing...
                        </span>
                    </button>
                </div>
            </x-slot>
            <x-slot name="description">
                Select a preset analysis or write your own question. The AI will analyze your live recruitment data.
            </x-slot>

            {{-- Preset Buttons --}}
            <div class="flex flex-wrap gap-2 mb-4">
                @foreach($presetLabels as $key => $label)
                    <button
                        type="button"
                        wire:click="selectPreset('{{ $key }}')"
                        class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium transition-all duration-150
                            {{ $selectedPreset === $key
                                ? 'bg-primary-500 text-white shadow-sm ring-1 ring-primary-500'
                                : 'bg-white dark:bg-white/5 text-gray-700 dark:text-gray-300 ring-1 ring-gray-200 dark:ring-white/10 hover:bg-gray-50 dark:hover:bg-white/10' }}"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            {{-- Question Input --}}
            <div>
                <x-filament::input.wrapper>
                    <textarea
                        wire:model="question"
                        placeholder="Ask anything about your recruitment data..."
                        rows="1"
                        x-data="{
                            resize() {
                                $el.style.height = 'auto';
                                const lineHeight = parseInt(getComputedStyle($el).lineHeight) || 22;
                                const maxHeight = lineHeight * 5;
                                const newHeight = Math.max($el.scrollHeight, lineHeight);
                                $el.style.height = Math.min(newHeight, maxHeight) + 'px';
                                $el.style.overflowY = newHeight > maxHeight ? 'auto' : 'hidden';
                            }
                        }"
                        x-init="$nextTick(() => resize())"
                        @input="resize()"
                        @keydown.enter="if (!$event.shiftKey && !$event.ctrlKey) { $event.preventDefault(); $wire.generate(); } "
                        class="block w-full border-none bg-transparent px-3 py-1.5 text-base text-gray-950 outline-none transition duration-75 placeholder:text-gray-400 focus:ring-0 dark:text-white dark:placeholder:text-gray-500 sm:text-sm sm:leading-6 resize-none"
                        style="overflow-y: hidden;"
                    ></textarea>
                </x-filament::input.wrapper>
                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Press Enter to generate · Shift+Enter or Ctrl+Enter for new line</p>
            </div>
        </x-filament::section>

        {{-- ═══ Loading Skeleton ═══ --}}
        <div wire:loading wire:target="generate" class="space-y-4">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @for($i = 0; $i < 4; $i++)
                <div class="rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 p-5 animate-pulse">
                    <div class="h-3 bg-gray-200 dark:bg-white/10 rounded w-24 mb-3"></div>
                    <div class="h-8 bg-gray-200 dark:bg-white/10 rounded w-16 mb-2"></div>
                    <div class="h-2 bg-gray-200 dark:bg-white/10 rounded w-32"></div>
                </div>
                @endfor
            </div>
            <div class="grid gap-4 lg:grid-cols-2">
                @for($i = 0; $i < 2; $i++)
                <div class="rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 p-6 animate-pulse">
                    <div class="h-4 bg-gray-200 dark:bg-white/10 rounded w-40 mb-4"></div>
                    <div class="h-48 bg-gray-200 dark:bg-white/10 rounded"></div>
                </div>
                @endfor
            </div>
        </div>

        {{-- ═══ Report Output ═══ --}}
        @if($report)
        <div wire:loading.remove wire:target="generate" class="space-y-6">

            {{-- Summary --}}
            @if(!empty($report['summary']))
            <div class="rounded-xl border border-purple-200 dark:border-purple-500/20 bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/10 dark:to-indigo-900/10 p-5">
                <div class="flex items-start gap-3">
                    <div class="shrink-0 rounded-lg bg-purple-100 dark:bg-purple-500/20 p-2">
                        <x-heroicon-o-light-bulb class="h-5 w-5 text-purple-600 dark:text-purple-400" />
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-purple-900 dark:text-purple-200 mb-1">Executive Summary</h3>
                        <p class="text-sm text-purple-800 dark:text-purple-300 leading-relaxed">{{ $report['summary'] }}</p>
                    </div>
                </div>
            </div>
            @endif

            {{-- KPI Cards --}}
            @if(!empty($report['kpi_cards']))
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-{{ min(count($report['kpi_cards']), 4) }}">
                @foreach($report['kpi_cards'] as $card)
                    @php
                        $colorMap = [
                            'blue'   => ['bg' => 'bg-blue-50 dark:bg-blue-900/10', 'border' => 'border-blue-200 dark:border-blue-500/20', 'text' => 'text-blue-600 dark:text-blue-400', 'value' => 'text-blue-900 dark:text-blue-100'],
                            'green'  => ['bg' => 'bg-emerald-50 dark:bg-emerald-900/10', 'border' => 'border-emerald-200 dark:border-emerald-500/20', 'text' => 'text-emerald-600 dark:text-emerald-400', 'value' => 'text-emerald-900 dark:text-emerald-100'],
                            'red'    => ['bg' => 'bg-red-50 dark:bg-red-900/10', 'border' => 'border-red-200 dark:border-red-500/20', 'text' => 'text-red-600 dark:text-red-400', 'value' => 'text-red-900 dark:text-red-100'],
                            'yellow' => ['bg' => 'bg-amber-50 dark:bg-amber-900/10', 'border' => 'border-amber-200 dark:border-amber-500/20', 'text' => 'text-amber-600 dark:text-amber-400', 'value' => 'text-amber-900 dark:text-amber-100'],
                            'purple' => ['bg' => 'bg-purple-50 dark:bg-purple-900/10', 'border' => 'border-purple-200 dark:border-purple-500/20', 'text' => 'text-purple-600 dark:text-purple-400', 'value' => 'text-purple-900 dark:text-purple-100'],
                            'indigo' => ['bg' => 'bg-indigo-50 dark:bg-indigo-900/10', 'border' => 'border-indigo-200 dark:border-indigo-500/20', 'text' => 'text-indigo-600 dark:text-indigo-400', 'value' => 'text-indigo-900 dark:text-indigo-100'],
                        ];
                        $c = $colorMap[$card['color'] ?? 'blue'] ?? $colorMap['blue'];
                        $trend = $card['trend'] ?? 'neutral';
                    @endphp
                    <div class="rounded-xl border {{ $c['border'] }} {{ $c['bg'] }} p-5 transition-all hover:shadow-md">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-semibold uppercase tracking-wide {{ $c['text'] }}">{{ $card['label'] ?? '' }}</span>
                            @if($trend === 'up')
                                <x-heroicon-m-arrow-trending-up class="h-4 w-4 text-emerald-500" />
                            @elseif($trend === 'down')
                                <x-heroicon-m-arrow-trending-down class="h-4 w-4 text-red-500" />
                            @else
                                <x-heroicon-m-minus class="h-4 w-4 text-gray-400" />
                            @endif
                        </div>
                        <p class="text-2xl font-bold {{ $c['value'] }} mb-1">{{ $card['value'] ?? '0' }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $card['description'] ?? '' }}</p>
                    </div>
                @endforeach
            </div>
            @endif

            {{-- Charts --}}
            @if(!empty($report['charts']))
            <div class="grid gap-6 lg:grid-cols-2">
                @foreach($report['charts'] as $index => $chart)
                <x-filament::section>
                    <x-slot name="heading">{{ $chart['title'] ?? 'Chart' }}</x-slot>
                    <div class="relative" style="height: 300px;">
                        <canvas
                            id="ai-chart-{{ $index }}"
                            x-init="renderChart('ai-chart-{{ $index }}', @js($chart))"
                        ></canvas>
                    </div>
                </x-filament::section>
                @endforeach
            </div>
            @endif

            {{-- Tables --}}
            @if(!empty($report['tables']))
            @foreach($report['tables'] as $tableIndex => $tableData)
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-table-cells class="h-5 w-5 text-indigo-500" />
                        <span>{{ $tableData['title'] ?? 'Data Table' }}</span>
                    </div>
                </x-slot>
                @if(!empty($tableData['description']))
                <x-slot name="description">{{ $tableData['description'] }}</x-slot>
                @endif

                <div class="space-y-4">
                    {{-- Export Button --}}
                    <div class="flex justify-end">
                        <button
                            type="button"
                            onclick="exportTableToCSV('ai-table-{{ $tableIndex }}', '{{ Str::slug($tableData['title'] ?? 'table') }}.csv')"
                            class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium bg-white dark:bg-white/5 text-gray-700 dark:text-gray-300 ring-1 ring-gray-200 dark:ring-white/10 hover:bg-gray-50 dark:hover:bg-white/10 transition-all"
                        >
                            <x-heroicon-o-arrow-down-tray class="h-4 w-4" />
                            Export CSV
                        </button>
                    </div>

                    {{-- Table --}}
                    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-white/10">
                        <table id="ai-table-{{ $tableIndex }}" class="min-w-full divide-y divide-gray-200 dark:divide-white/10">
                            <thead class="bg-gray-50 dark:bg-white/5">
                                <tr>
                                    @foreach($tableData['columns'] ?? [] as $column)
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                        {{ $column['label'] ?? $column['key'] ?? '' }}
                                    </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-transparent divide-y divide-gray-200 dark:divide-white/5">
                                @foreach($tableData['rows'] ?? [] as $row)
                                <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                                    @foreach($tableData['columns'] ?? [] as $column)
                                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100 whitespace-nowrap">
                                        {{ $row[$column['key']] ?? '—' }}
                                    </td>
                                    @endforeach
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if(empty($tableData['rows']))
                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <x-heroicon-o-inbox class="h-12 w-12 mx-auto mb-2 opacity-50" />
                        <p class="text-sm">No data available</p>
                    </div>
                    @endif
                </div>
            </x-filament::section>
            @endforeach
            @endif

            {{-- Insights --}}
            @if(!empty($report['insights']))
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-eye class="h-5 w-5 text-blue-500" />
                        <span>Key Insights</span>
                    </div>
                </x-slot>
                <ul class="space-y-3">
                    @foreach($report['insights'] as $insight)
                    <li class="flex items-start gap-3 text-sm text-gray-700 dark:text-gray-300">
                        <span class="shrink-0 mt-1 h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                        <span>{{ $insight }}</span>
                    </li>
                    @endforeach
                </ul>
            </x-filament::section>
            @endif

            {{-- Recommendations --}}
            @if(!empty($report['recommendations']))
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-clipboard-document-check class="h-5 w-5 text-emerald-500" />
                        <span>Recommendations</span>
                    </div>
                </x-slot>
                <div class="space-y-3">
                    @foreach($report['recommendations'] as $rec)
                        @php
                            $priorityColors = [
                                'high'   => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                                'medium' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
                                'low'    => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
                            ];
                            $pColor = $priorityColors[$rec['priority'] ?? 'medium'] ?? $priorityColors['medium'];
                        @endphp
                        <div class="flex items-start gap-3 rounded-lg border border-gray-100 dark:border-white/5 bg-gray-50 dark:bg-white/[.02] p-4">
                            <span class="shrink-0 inline-flex items-center rounded-md px-2 py-1 text-xs font-medium {{ $pColor }}">
                                {{ ucfirst($rec['priority'] ?? 'medium') }}
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $rec['title'] ?? '' }}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-0.5">{{ $rec['description'] ?? '' }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
            @endif

        </div>
        @endif
    </div>

    {{-- Chart.js from CDN --}}
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script>
        // Keep track of chart instances to destroy before re-rendering
        window.__aiCharts = window.__aiCharts || {};

        function exportTableToCSV(tableId, filename) {
            const table = document.getElementById(tableId);
            if (!table) return;

            let csv = [];
            const rows = table.querySelectorAll('tr');

            for (let i = 0; i < rows.length; i++) {
                const row = [], cols = rows[i].querySelectorAll('td, th');
                for (let j = 0; j < cols.length; j++) {
                    let data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, '').replace(/(\s\s)/gm, ' ');
                    data = data.replace(/"/g, '""');
                    row.push('"' + data + '"');
                }
                csv.push(row.join(','));
            }

            const csvFile = new Blob([csv.join('\n')], { type: 'text/csv' });
            const downloadLink = document.createElement('a');
            downloadLink.download = filename;
            downloadLink.href = window.URL.createObjectURL(csvFile);
            downloadLink.style.display = 'none';
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
        }

        function aiReportApp() {
            return {
                renderChart(canvasId, chartConfig) {
                    this.$nextTick(() => {
                        const canvas = document.getElementById(canvasId);
                        if (!canvas) return;

                        // Destroy previous instance if exists
                        if (window.__aiCharts[canvasId]) {
                            window.__aiCharts[canvasId].destroy();
                        }

                        const ctx = canvas.getContext('2d');

                        // Map horizontalBar to bar with indexAxis
                        let chartType = chartConfig.type || 'bar';
                        let indexAxis = undefined;
                        if (chartType === 'horizontalBar') {
                            chartType = 'bar';
                            indexAxis = 'y';
                        }

                        // Build datasets — ensure colors are arrays for bar/doughnut
                        const datasets = (chartConfig.datasets || []).map(ds => {
                            const dataset = {
                                label: ds.label || '',
                                data: ds.data || [],
                                backgroundColor: ds.backgroundColor || '#3B82F6',
                            };

                            if (chartType === 'line') {
                                dataset.borderColor = Array.isArray(ds.backgroundColor) ? ds.backgroundColor[0] : (ds.backgroundColor || '#3B82F6');
                                dataset.backgroundColor = (Array.isArray(ds.backgroundColor) ? ds.backgroundColor[0] : (ds.backgroundColor || '#3B82F6')) + '20';
                                dataset.fill = true;
                                dataset.tension = 0.3;
                                dataset.pointRadius = 4;
                                dataset.pointHoverRadius = 6;
                            }

                            return dataset;
                        });

                        window.__aiCharts[canvasId] = new Chart(ctx, {
                            type: chartType,
                            data: {
                                labels: chartConfig.labels || [],
                                datasets: datasets,
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                indexAxis: indexAxis,
                                plugins: {
                                    legend: {
                                        display: chartType === 'doughnut' || datasets.length > 1,
                                        position: chartType === 'doughnut' ? 'right' : 'top',
                                        labels: {
                                            usePointStyle: true,
                                            padding: 16,
                                            font: { size: 12 },
                                        },
                                    },
                                    tooltip: {
                                        backgroundColor: 'rgba(0,0,0,0.8)',
                                        padding: 12,
                                        cornerRadius: 8,
                                        titleFont: { size: 13 },
                                        bodyFont: { size: 12 },
                                    },
                                },
                                scales: chartType === 'doughnut' ? {} : {
                                    x: {
                                        grid: { display: false },
                                        ticks: { font: { size: 11 } },
                                    },
                                    y: {
                                        beginAtZero: true,
                                        grid: { color: 'rgba(0,0,0,0.05)' },
                                        ticks: { font: { size: 11 } },
                                    },
                                },
                            },
                        });
                    });
                },
            };
        }
    </script>
    @endpush
</x-filament-panels::page>

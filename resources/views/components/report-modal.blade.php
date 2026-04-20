@props(['reportId'])

@php
    $report = \App\Models\AiGeneratedReport::find($reportId);
    if (!$report) return;
    $reportData = $report->report_json;
@endphp

<div 
    x-data="{ 
        open: false,
        reportData: @js($reportData),
        renderCharts() {
            this.$nextTick(() => {
                if (!this.reportData.charts) return;
                this.reportData.charts.forEach((chart, index) => {
                    const canvas = document.getElementById('modal-chart-' + index);
                    if (!canvas) return;
                    
                    if (window.__modalCharts && window.__modalCharts['modal-chart-' + index]) {
                        window.__modalCharts['modal-chart-' + index].destroy();
                    }
                    
                    const ctx = canvas.getContext('2d');
                    let chartType = chart.type || 'bar';
                    let indexAxis = undefined;
                    if (chartType === 'horizontalBar') {
                        chartType = 'bar';
                        indexAxis = 'y';
                    }
                    
                    const datasets = (chart.datasets || []).map(ds => {
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
                    
                    window.__modalCharts = window.__modalCharts || {};
                    window.__modalCharts['modal-chart-' + index] = new Chart(ctx, {
                        type: chartType,
                        data: {
                            labels: chart.labels || [],
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
            });
        }
    }"
    @open-report-modal.window="if ($event.detail.reportId === {{ $reportId }}) { open = true; renderCharts(); }"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
    @click.self="open = false"
>
    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl max-w-6xl w-full max-h-[90vh] overflow-hidden flex flex-col">
        <!-- Header -->
        <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <x-heroicon-o-chart-bar class="h-6 w-6 text-indigo-500" />
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">{{ $report->title }}</h2>
            </div>
            <button @click="open = false" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                <x-heroicon-o-x-mark class="h-6 w-6" />
            </button>
        </div>
        
        <!-- Content -->
        <div class="flex-1 overflow-y-auto p-6 space-y-6">
            {{-- Summary --}}
            @if(!empty($reportData['summary']))
            <div class="rounded-xl border border-purple-200 dark:border-purple-500/20 bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/10 dark:to-indigo-900/10 p-5">
                <div class="flex items-start gap-3">
                    <div class="shrink-0 rounded-lg bg-purple-100 dark:bg-purple-500/20 p-2">
                        <x-heroicon-o-light-bulb class="h-5 w-5 text-purple-600 dark:text-purple-400" />
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-purple-900 dark:text-purple-200 mb-1">Executive Summary</h3>
                        <p class="text-sm text-purple-800 dark:text-purple-300 leading-relaxed">{{ $reportData['summary'] }}</p>
                    </div>
                </div>
            </div>
            @endif
            
            {{-- KPI Cards --}}
            @if(!empty($reportData['kpi_cards']))
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-{{ min(count($reportData['kpi_cards']), 4) }}">
                @foreach($reportData['kpi_cards'] as $card)
                    @php
                        $colorMap = [
                            'blue'   => ['bg' => 'bg-blue-50 dark:bg-blue-900/10', 'border' => 'border-blue-200 dark:border-blue-500/20', 'text' => 'text-blue-600 dark:text-blue-400', 'value' => 'text-blue-900 dark:text-blue-100'],
                            'green'  => ['bg' => 'bg-emerald-50 dark:bg-emerald-900/10', 'border' => 'border-emerald-200 dark:border-emerald-500/20', 'text' => 'text-emerald-600 dark:text-emerald-400', 'value' => 'text-emerald-900 dark:text-emerald-100'],
                            'red'    => ['bg' => 'bg-red-50 dark:bg-red-900/10', 'border' => 'border-red-200 dark:border-red-500/20', 'text' => 'text-red-600 dark:text-red-400', 'value' => 'text-red-900 dark:text-red-100'],
                            'yellow' => ['bg' => 'bg-amber-50 dark:bg-amber-900/10', 'border' => 'border-amber-200 dark:border-amber-500/20', 'text' => 'text-amber-600 dark:text-amber-400', 'value' => 'text-amber-900 dark:text-amber-100'],
                            'purple' => ['bg' => 'bg-purple-50 dark:bg-purple-900/10', 'border' => 'border-purple-200 dark:border-purple-500/20', 'text' => 'text-purple-600 dark:text-purple-400', 'value' => 'text-purple-900 dark:text-purple-100'],
                        ];
                        $c = $colorMap[$card['color'] ?? 'blue'] ?? $colorMap['blue'];
                        $trend = $card['trend'] ?? 'neutral';
                    @endphp
                    <div class="rounded-xl border {{ $c['border'] }} {{ $c['bg'] }} p-5">
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
            @if(!empty($reportData['charts']))
            <div class="grid gap-6 lg:grid-cols-2">
                @foreach($reportData['charts'] as $index => $chart)
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4">{{ $chart['title'] ?? 'Chart' }}</h3>
                    <div class="relative" style="height: 300px;">
                        <canvas id="modal-chart-{{ $index }}"></canvas>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
            
            {{-- Insights --}}
            @if(!empty($reportData['insights']))
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6">
                <div class="flex items-center gap-2 mb-4">
                    <x-heroicon-o-eye class="h-5 w-5 text-blue-500" />
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Key Insights</h3>
                </div>
                <ul class="space-y-3">
                    @foreach($reportData['insights'] as $insight)
                    <li class="flex items-start gap-3 text-sm text-gray-700 dark:text-gray-300">
                        <span class="shrink-0 mt-1 h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                        <span>{{ $insight }}</span>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif
            
            {{-- Recommendations --}}
            @if(!empty($reportData['recommendations']))
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6">
                <div class="flex items-center gap-2 mb-4">
                    <x-heroicon-o-clipboard-document-check class="h-5 w-5 text-emerald-500" />
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Recommendations</h3>
                </div>
                <div class="space-y-3">
                    @foreach($reportData['recommendations'] as $rec)
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
            </div>
            @endif
        </div>
        
        <!-- Footer Actions -->
        <div class="flex items-center justify-end gap-3 p-6 border-t border-gray-200 dark:border-gray-700">
            @if(!$report->is_saved)
            <x-filament::button wire:click="saveReport({{ $reportId }})" icon="heroicon-o-bookmark" color="primary">
                Save Report
            </x-filament::button>
            @endif
            
            <x-filament::button wire:click="exportReportPdf({{ $reportId }})" icon="heroicon-o-document-arrow-down" color="gray" outlined>
                Export PDF
            </x-filament::button>
            
            <x-filament::button wire:click="exportReportCsv({{ $reportId }})" icon="heroicon-o-arrow-down-tray" color="gray" outlined>
                Export CSV
            </x-filament::button>
        </div>
    </div>
</div>

<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-xl bg-white px-6 py-8 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white sm:text-3xl">M&E Dashboard</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ now()->format('l, F j, Y') }} &nbsp;·&nbsp; FY {{ now()->year }}
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    @php
                        $heroKpis = [
                            ['label' => 'Active Projects', 'value' => $projectsCount, 'icon' => 'heroicon-o-rectangle-stack'],
                            ['label' => 'Indicators', 'value' => $indicatorsCount, 'icon' => 'heroicon-o-chart-bar-square'],
                            ['label' => 'Reports This Month', 'value' => $reportsThisMonth, 'icon' => 'heroicon-o-calendar-days'],
                            ['label' => 'Needs Attention', 'value' => $needsAttention, 'icon' => 'heroicon-o-exclamation-triangle'],
                        ];
                    @endphp

                    @foreach ($heroKpis as $kpi)
                        <div class="flex items-center gap-3 rounded-xl bg-gray-50 ring-1 ring-inset ring-gray-950/5 px-4 py-3 dark:bg-white/5 dark:ring-white/10">
                            <x-filament::icon :icon="$kpi['icon']" class="h-6 w-6 shrink-0 opacity-80 text-gray-400 dark:text-gray-500" />
                            <div>
                                <div class="text-xl font-bold leading-none text-gray-950 dark:text-white">{{ number_format((int) $kpi['value']) }}</div>
                                <div class="mt-0.5 text-[11px] font-medium uppercase tracking-wider opacity-70">{{ $kpi['label'] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @php
                $strips = [
                    [
                        'label' => 'Coverage Rate',
                        'value' => $coverageRate . '%',
                        'sub' => 'reported vs total indicators',
                        'icon' => 'heroicon-o-check-badge',
                        'iconBg' => 'bg-success-50 dark:bg-success-500/10',
                        'iconText' => 'text-success-600 dark:text-success-400',
                    ],
                    [
                        'label' => 'On Track',
                        'value' => $onTrack,
                        'sub' => 'currently healthy indicators',
                        'icon' => 'heroicon-o-arrow-trending-up',
                        'iconBg' => 'bg-primary-50 dark:bg-primary-500/10',
                        'iconText' => 'text-primary-600 dark:text-primary-400',
                    ],
                    [
                        'label' => 'Off Track',
                        'value' => $offTrack,
                        'sub' => 'requires corrective action',
                        'icon' => 'heroicon-o-arrow-trending-down',
                        'iconBg' => $offTrack > 0 ? 'bg-danger-50 dark:bg-danger-500/10' : 'bg-gray-50 dark:bg-gray-800',
                        'iconText' => $offTrack > 0 ? 'text-danger-600 dark:text-danger-400' : 'text-gray-400',
                    ],
                    [
                        'label' => 'Unreported',
                        'value' => $unreportedIndicators,
                        'sub' => 'indicators without reports',
                        'icon' => 'heroicon-o-clock',
                        'iconBg' => $unreportedIndicators > 0 ? 'bg-warning-50 dark:bg-warning-500/10' : 'bg-gray-50 dark:bg-gray-800',
                        'iconText' => $unreportedIndicators > 0 ? 'text-warning-600 dark:text-warning-400' : 'text-gray-400',
                    ],
                ];
            @endphp

            @foreach ($strips as $s)
                <div class="flex items-center gap-4 rounded-xl bg-white px-5 py-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl {{ $s['iconBg'] }}">
                        <x-filament::icon :icon="$s['icon']" class="h-6 w-6 {{ $s['iconText'] }}" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <span class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
                            {{ is_numeric($s['value']) ? number_format((int) $s['value']) : $s['value'] }}
                        </span>
                        <div class="text-sm font-semibold text-gray-950 dark:text-white leading-tight">{{ $s['label'] }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $s['sub'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div class="rounded-xl bg-white px-5 py-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="text-xs text-gray-500">Reported Indicators</div>
                <div class="text-2xl font-semibold">{{ number_format((int) $reportedIndicators) }}</div>
            </div>
            <div class="rounded-xl bg-white px-5 py-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="text-xs text-gray-500">Total Report Entries</div>
                <div class="text-2xl font-semibold">{{ number_format((int) $reportRows) }}</div>
            </div>
            <div class="rounded-xl bg-white px-5 py-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="text-xs text-gray-500">Latest Report Date</div>
                <div class="text-2xl font-semibold">{{ $latestReportDate ?: '-' }}</div>
            </div>
        </div>

        <x-filament-widgets::widgets
            :widgets="$this->getWidgets()"
            :columns="$this->getColumns()"
        />
    </div>
</x-filament-panels::page>

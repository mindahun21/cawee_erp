<x-filament-panels::page>

    <div class="space-y-6">

        {{-- ══════════════════════════════════════════════════════════════ --}}
        {{-- HERO BANNER                                                     --}}
        {{-- ══════════════════════════════════════════════════════════════ --}}
        <div class="rounded-xl bg-white px-6 py-8 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                {{-- Title --}}
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white sm:text-3xl">Planning Overview</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ now()->format('l, F j, Y') }} &nbsp;·&nbsp; Performance Tracking
                    </p>
                </div>

                {{-- Inline KPIs --}}
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                    @php
                        $heroKpis = [
                            ['label' => 'Active Plans',    'value' => $totalActivePlans,   'icon' => 'heroicon-o-clipboard-document-list', 'cls' => 'bg-gray-50 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10'],
                            ['label' => 'Avg Progress',    'value' => $avgProgress . '%',  'icon' => 'heroicon-o-chart-bar-square', 'cls' => 'bg-gray-50 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10'],
                            ['label' => 'Overdue Tasks',   'value' => $overdueTasks,       'icon' => 'heroicon-o-exclamation-circle', 'cls' => $overdueTasks > 0 ? 'bg-danger-50 ring-danger-500/20 text-danger-600 dark:bg-danger-500/10 dark:text-danger-400 dark:ring-danger-500/20' : 'bg-gray-50 ring-gray-950/5 dark:bg-white/5 dark:text-gray-400'],
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
        {{-- EXECUTION SUMMARY STRIP                                          --}}
        {{-- ══════════════════════════════════════════════════════════════ --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @php
                $strips = [
                    [
                        'label'    => 'Monthly Completion',
                        'value'    => $completedTasksMonth,
                        'sub'      => 'Tasks finished in ' . now()->format('F'),
                        'icon'     => 'heroicon-o-check-badge',
                        'iconBg'   => 'bg-success-50 dark:bg-success-500/10',
                        'iconText' => 'text-success-600 dark:text-success-400',
                    ],
                    [
                        'label'    => 'KPI Tracking',
                        'value'    => $totalKpis,
                        'sub'      => 'Active indicators',
                        'icon'     => 'heroicon-o-presentation-chart-bar',
                        'iconBg'   => 'bg-primary-50 dark:bg-primary-500/10',
                        'iconText' => 'text-primary-600 dark:text-primary-400',
                    ],
                    [
                        'label'    => 'Underperforming',
                        'value'    => $underperformingKpis,
                        'sub'      => 'KPIs below target',
                        'icon'     => 'heroicon-o-arrow-trending-down',
                        'iconBg'   => $underperformingKpis > 0 ? 'bg-danger-50 dark:bg-danger-500/10' : 'bg-gray-50 dark:bg-gray-800',
                        'iconText' => $underperformingKpis > 0 ? 'text-danger-600 dark:text-danger-400' : 'text-gray-400',
                    ],
                    [
                        'label'    => 'Active Workload',
                        'value'    => $activeTasks,
                        'sub'      => 'Pending tasks across plans',
                        'icon'     => 'heroicon-o-briefcase',
                        'iconBg'   => 'bg-warning-50 dark:bg-warning-500/10',
                        'iconText' => 'text-warning-600 dark:text-warning-400',
                    ],
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

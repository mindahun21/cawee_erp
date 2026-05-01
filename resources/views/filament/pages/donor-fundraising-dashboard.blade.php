<x-filament-panels::page>

    <div class="space-y-6">

        {{-- ══════════════════════════════════════════════════════════════ --}}
        {{-- HERO BANNER                                                     --}}
        {{-- ══════════════════════════════════════════════════════════════ --}}
        <div class="rounded-xl bg-white px-6 py-8 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                {{-- Title --}}
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white sm:text-3xl">Donor Fundraising Dashboard</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ now()->format('l, F j, Y') }} &nbsp;·&nbsp; FY {{ now()->year }}
                    </p>
                </div>

                {{-- Inline KPIs --}}
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    @php
                        $heroKpis = [
                            ['label' => 'Total Donors',      'value' => number_format($totalDonors),    'icon' => 'heroicon-o-users',         'cls' => 'bg-gray-50 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10'],
                            ['label' => 'Active Campaigns',  'value' => number_format($activeCampaigns),'icon' => 'heroicon-o-flag',          'cls' => 'bg-gray-50 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10'],
                            ['label' => 'Donations Today',   'value' => number_format($donationsToday), 'icon' => 'heroicon-o-gift',          'cls' => $donationsToday > 0 ? 'bg-success-50 ring-success-500/20 text-success-600 dark:bg-success-500/10 dark:text-success-400 dark:ring-success-500/20' : 'bg-gray-50 ring-gray-950/5 dark:bg-white/5 dark:text-gray-400'],
                            ['label' => 'Raised Today (Est.)',      'value' => 'ETB '.number_format($raisedToday),'icon' => 'heroicon-o-currency-dollar','cls' => $raisedToday > 0 ? 'bg-primary-50 ring-primary-500/20 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400 dark:ring-primary-500/20' : 'bg-gray-50 ring-gray-950/5 dark:bg-white/5 dark:text-gray-400'],
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
        {{-- MONTHLY SUMMARY STRIP                                          --}}
        {{-- ══════════════════════════════════════════════════════════════ --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @php
                $strips = [
                    [
                        'label'    => 'New Donors',
                        'value'    => number_format($newDonorsThisMonth),
                        'sub'      => now()->format('F'),
                        'icon'     => 'heroicon-o-user-plus',
                        'iconBg'   => 'bg-success-50 dark:bg-success-500/10',
                        'iconText' => 'text-success-600 dark:text-success-400',
                        'trend'    => $newDonorsThisMonth > 0 ? 'up' : null,
                    ],
                    [
                        'label'    => 'Donations',
                        'value'    => number_format($donationsThisMonth),
                        'sub'      => now()->format('F'),
                        'icon'     => 'heroicon-o-gift',
                        'iconBg'   => 'bg-primary-50 dark:bg-primary-500/10',
                        'iconText' => 'text-primary-600 dark:text-primary-400',
                        'trend'    => $donationsThisMonth > 0 ? 'up' : null,
                    ],
                    [
                        'label'    => 'Funds Raised (Est.)',
                        'value'    => 'ETB ' . number_format($raisedThisMonth),
                        'sub'      => now()->format('F'),
                        'icon'     => 'heroicon-o-banknotes',
                        'iconBg'   => 'bg-warning-50 dark:bg-warning-500/10',
                        'iconText' => 'text-warning-600 dark:text-warning-400',
                        'trend'    => $raisedThisMonth > 0 ? 'up' : null,
                    ],
                    [
                        'label'    => 'Avg. Donation (Est.)',
                        'value'    => 'ETB ' . number_format($avgDonationThisMonth),
                        'sub'      => now()->format('F'),
                        'icon'     => 'heroicon-o-chart-bar',
                        'iconBg'   => 'bg-info-50 dark:bg-info-500/10',
                        'iconText' => 'text-info-600 dark:text-info-400',
                        'trend'    => null,
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
                            @if($s['trend'] === 'up')
                                <x-filament::icon icon="heroicon-s-arrow-trending-up" class="h-4 w-4 text-success-500" />
                            @elseif($s['trend'] === 'down')
                                <x-filament::icon icon="heroicon-s-arrow-trending-down" class="h-4 w-4 text-danger-500" />
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

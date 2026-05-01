<x-filament-panels::page>
    <div class="space-y-6" wire:loading.class="opacity-50 transition-opacity duration-300">
        <div wire:loading class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/20 backdrop-blur-[1px]">
            <div class="flex items-center gap-3 rounded-lg bg-white p-4 shadow-xl dark:bg-gray-800">
                <svg class="h-6 w-6 animate-spin text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-sm font-medium text-gray-900 dark:text-white">Updating Analytics...</span>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════════ --}}
        {{-- FILTERS SECTION                                                --}}
        {{-- ══════════════════════════════════════════════════════════════ --}}
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            {{ $this->form }}
        </div>

        {{-- ══════════════════════════════════════════════════════════════ --}}
        {{-- HERO BANNER (Global Summary)                                   --}}
        {{-- ══════════════════════════════════════════════════════════════ --}}
        <div class="rounded-xl bg-white px-6 py-8 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white sm:text-3xl">Fundraising Performance</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Overview of your NGO's financial health and donor engagement.
                    </p>
                </div>

                {{-- Inline KPIs --}}
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @php
                        $heroKpis = [
                            ['label' => 'Global Donors',      'value' => number_format($totalDonors),    'icon' => 'heroicon-o-users',         'cls' => 'bg-gray-50 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10'],
                            ['label' => 'Total Donations',   'value' => number_format($totalDonationsAllTime),'icon' => 'heroicon-o-gift',    'cls' => 'bg-gray-50 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10'],
                            ['label' => 'Active Campaigns',  'value' => number_format($activeCampaigns),'icon' => 'heroicon-o-flag',          'cls' => 'bg-gray-50 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10'],
                        ];
                    @endphp

                    @foreach($heroKpis as $kpi)
                        <div class="flex items-center gap-3 rounded-xl {{ $kpi['cls'] }} ring-1 ring-inset px-4 py-3">
                            <x-filament::icon :icon="$kpi['icon']" class="h-6 w-6 shrink-0 opacity-80 text-gray-400 dark:text-gray-500" />
                            <div>
                                <div class="text-xl font-bold leading-none text-gray-950 dark:text-white">{{ $kpi['value'] }}</div>
                                <div class="mt-1 text-[11px] font-medium uppercase tracking-wider opacity-70">{{ $kpi['label'] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════════ --}}
        {{-- LIFETIME IMPACT (ENTITY FILTERED)                              --}}
        {{-- ══════════════════════════════════════════════════════════════ --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="rounded-xl bg-gray-950 px-6 py-4 shadow-sm dark:bg-gray-800 ring-1 ring-white/10">
                <div class="flex items-center gap-4">
                    <div class="rounded-lg bg-primary-500/20 p-2 text-primary-400">
                        <x-filament::icon icon="heroicon-o-banknotes" class="h-6 w-6" />
                    </div>
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Total Funds Raised (Lifetime)</div>
                        <div class="text-2xl font-black tracking-tight text-white leading-none mt-0.5">ETB {{ \App\Filament\Pages\DonorFundraisingDashboard::formatLargeNumber($totalRaisedAllTime) }}</div>
                    </div>
                </div>
            </div>
            <div class="rounded-xl bg-gray-950 px-6 py-4 shadow-sm dark:bg-gray-800 ring-1 ring-white/10">
                <div class="flex items-center gap-4">
                    <div class="rounded-lg bg-success-500/20 p-2 text-success-400">
                        <x-filament::icon icon="heroicon-o-calculator" class="h-6 w-6" />
                    </div>
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Lifetime Avg. Donation</div>
                        <div class="text-2xl font-black tracking-tight text-white leading-none mt-0.5">ETB {{ \App\Filament\Pages\DonorFundraisingDashboard::formatLargeNumber($totalAvgDonationAllTime) }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════════ --}}
        {{-- PERIOD PERFORMANCE STRIP (REACTIVE)                            --}}
        {{-- ══════════════════════════════════════════════════════════════ --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @php
                $strips = [
                    [
                        'label'    => 'Unique Donors',
                        'value'    => number_format($uniqueDonors),
                        'sub'      => $filterLabel,
                        'icon'     => 'heroicon-o-user-group',
                        'iconBg'   => 'bg-success-50 dark:bg-success-500/10',
                        'iconText' => 'text-success-600 dark:text-success-400',
                        'trend'    => null,
                    ],
                    [
                        'label'    => 'Total Donations',
                        'value'    => number_format($donationsCount),
                        'sub'      => $filterLabel,
                        'icon'     => 'heroicon-o-gift',
                        'iconBg'   => 'bg-primary-50 dark:bg-primary-500/10',
                        'iconText' => 'text-primary-600 dark:text-primary-400',
                        'trend'    => null,
                    ],
                    [
                        'label'    => 'Funds Raised',
                        'value'    => 'ETB ' . \App\Filament\Pages\DonorFundraisingDashboard::formatLargeNumber($raisedAmount),
                        'sub'      => $filterLabel,
                        'icon'     => 'heroicon-o-banknotes',
                        'iconBg'   => 'bg-warning-50 dark:bg-warning-500/10',
                        'iconText' => 'text-warning-600 dark:text-warning-400',
                        'trend'    => null,
                    ],
                    [
                        'label'    => 'Avg. Donation',
                        'value'    => 'ETB ' . \App\Filament\Pages\DonorFundraisingDashboard::formatLargeNumber($avgDonation),
                        'sub'      => $filterLabel,
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
                            @if(($s['trend'] ?? null) === 'up')
                                <x-filament::icon icon="heroicon-s-arrow-trending-up" class="h-4 w-4 text-success-500" />
                            @elseif(($s['trend'] ?? null) === 'down')
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

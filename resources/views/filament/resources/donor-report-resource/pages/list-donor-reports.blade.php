<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
        @php
            $reports = [
                [
                    'title'       => 'Donation Summary',
                    'description' => 'A comprehensive tabular view of all donations with robust filtering.',
                    'icon'        => 'heroicon-o-table-cells',
                    'route'       => 'filament.admin.resources.donor-reports.summary',
                    'color'       => 'text-primary-600 bg-primary-50 dark:text-primary-400 dark:bg-primary-500/10',
                ],
                [
                    'title'       => 'Geography Report',
                    'description' => 'Visual map and table breakdown of donations across different regions and cities.',
                    'icon'        => 'heroicon-o-map',
                    'route'       => 'filament.admin.resources.donor-reports.geography',
                    'color'       => 'text-success-600 bg-success-50 dark:text-success-400 dark:bg-success-500/10',
                ],
                [
                    'title'       => 'Donation Trends',
                    'description' => 'Interactive monthly trends analyzing donor engagement and volume over time.',
                    'icon'        => 'heroicon-o-chart-bar',
                    'route'       => 'filament.admin.resources.donor-reports.trends',
                    'color'       => 'text-warning-600 bg-warning-50 dark:text-warning-400 dark:bg-warning-500/10',
                ],
            ];
        @endphp

        @foreach($reports as $report)
            <div class="rounded-xl flex flex-col justify-between bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6 transition hover:shadow-md hover:ring-primary-500/50">
                <div>
                    <div class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-lg {{ $report['color'] }}">
                        <x-filament::icon :icon="$report['icon']" class="h-6 w-6" />
                    </div>
                    <h3 class="text-xl font-bold tracking-tight text-gray-950 dark:text-white mb-2">
                        {{ $report['title'] }}
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-8 font-medium leading-relaxed">
                        {{ $report['description'] }}
                    </p>
                </div>
                
                <x-filament::button tag="a" href="{{ route($report['route']) }}" color="primary" class="w-full justify-center">
                    View Report
                </x-filament::button>
            </div>
        @endforeach
    </div>
</x-filament-panels::page>

<x-filament-panels::page>
    {{-- Summary Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
        <x-filament::section>
            <div class="flex items-center gap-4">
                <div class="p-2 bg-primary-50 dark:bg-primary-900/10 rounded-lg">
                    <x-filament::icon icon="heroicon-o-megaphone" class="w-6 h-6 text-primary-600" />
                </div>
                <div>
                    <div class="text-xs text-gray-500 font-medium uppercase tracking-wider">Total Campaigns</div>
                    <div class="text-2xl font-bold">{{ number_format($stats['total_campaigns']) }}</div>
                    <div class="text-xs text-success-600 font-medium flex items-center gap-1">
                        <x-filament::icon icon="heroicon-m-play-circle" class="w-3 h-3" />
                        {{ number_format($stats['active_count']) }} active
                    </div>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="flex items-center gap-4">
                <div class="p-2 bg-success-50 dark:bg-success-900/10 rounded-lg">
                    <x-filament::icon icon="heroicon-o-currency-dollar" class="w-6 h-6 text-success-600" />
                </div>
                <div>
                    <div class="text-xs text-gray-500 font-medium uppercase tracking-wider">Total Raised</div>
                    <div class="text-2xl font-bold">${{ number_format($stats['total_raised'], 2) }}</div>
                    <div class="text-xs text-gray-500 font-medium">
                        {{ number_format($stats['avg_progress'], 1) }}% of goal
                    </div>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="flex items-center gap-4">
                <div class="p-2 bg-info-50 dark:bg-info-900/10 rounded-lg">
                    <x-filament::icon icon="heroicon-o-users" class="w-6 h-6 text-info-600" />
                </div>
                <div>
                    <div class="text-xs text-gray-500 font-medium uppercase tracking-wider">Total Donors</div>
                    <div class="text-2xl font-bold">{{ number_format($stats['total_donors']) }}</div>
                    <div class="text-xs text-gray-500 font-medium">
                        {{ $stats['total_campaigns'] > 0 ? number_format($stats['total_donors'] / $stats['total_campaigns'], 1) : 0 }} avg per campaign
                    </div>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="flex items-center gap-4">
                <div class="p-2 bg-warning-50 dark:bg-warning-900/10 rounded-lg">
                    <x-filament::icon icon="heroicon-o-chart-bar" class="w-6 h-6 text-warning-600" />
                </div>
                <div>
                    <div class="text-xs text-gray-500 font-medium uppercase tracking-wider">Goal Achievement</div>
                    <div class="text-2xl font-bold">{{ number_format($stats['avg_progress'], 1) }}%</div>
                    <div class="text-xs text-success-600 font-medium flex items-center gap-1">
                        <x-filament::icon icon="heroicon-m-check-circle" class="w-3 h-3" />
                        {{ number_format($stats['completed_count']) }} met goal
                    </div>
                </div>
            </div>
        </x-filament::section>
    </div>

    {{-- Details Table --}}
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-filament::icon icon="heroicon-o-table-cells" class="h-5 w-5 text-gray-400" />
                <span>Campaign Performance Details</span>
            </div>
        </x-slot>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm divide-y divide-gray-200 dark:divide-white/10">
                <thead>
                    <tr class="bg-gray-50 dark:bg-white/5">
                        <th class="px-4 py-3 font-semibold">Campaign</th>
                        <th class="px-4 py-3 font-semibold">Status</th>
                        <th class="px-4 py-3 font-semibold text-right">Goal</th>
                        <th class="px-4 py-3 font-semibold text-right">Raised</th>
                        <th class="px-4 py-3 font-semibold">Progress</th>
                        <th class="px-4 py-3 font-semibold text-center">Donors</th>
                        <th class="px-4 py-3 font-semibold text-center">Donations</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                    @forelse($campaigns as $campaign)
                        <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                            <td class="px-4 py-4">
                                <div class="font-medium text-gray-950 dark:text-white">{{ $campaign['title'] }}</div>
                                <div class="text-xs text-gray-500 mt-1">
                                    {{ \Carbon\Carbon::parse($campaign['start_date'])->format('M d, Y') }}
                                    @if($campaign['end_date'])
                                        - {{ \Carbon\Carbon::parse($campaign['end_date'])->format('M d, Y') }}
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <x-filament::badge 
                                    :color="match($campaign['status']) {
                                        'active' => 'success',
                                        'completed' => 'primary',
                                        'draft' => 'gray',
                                        'scheduled' => 'info',
                                        'archived' => 'danger',
                                        default => 'warning'
                                    }"
                                >
                                    {{ ucfirst($campaign['status']) }}
                                </x-filament::badge>
                            </td>
                            <td class="px-4 py-4 text-right font-medium">
                                ${{ number_format($campaign['goal_amount'], 2) }}
                            </td>
                            <td class="px-4 py-4 text-right">
                                <div class="font-bold text-success-600 dark:text-success-400">${{ number_format($campaign['total_raised'], 2) }}</div>
                                <div class="text-xs text-gray-500">{{ number_format($campaign['progress_percentage'], 1) }}% of goal</div>
                            </td>
                            <td class="px-4 py-4 w-32">
                                <div class="flex items-center gap-2">
                                    <div class="flex-grow bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                                        <div 
                                            class="h-1.5 rounded-full bg-{{ $campaign['progress_percentage'] >= 100 ? 'success' : 'primary' }}-600" 
                                            style="width: {{ min(100, $campaign['progress_percentage']) }}%"
                                        ></div>
                                    </div>
                                    <span class="text-xs text-gray-500">{{ number_format($campaign['progress_percentage'], 0) }}%</span>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-center font-medium">{{ number_format($campaign['donor_count']) }}</td>
                            <td class="px-4 py-4 text-center font-medium">{{ number_format($campaign['donation_count']) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                No campaigns found matching your criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>

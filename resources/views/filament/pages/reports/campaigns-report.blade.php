<x-filament-panels::page>
    <form wire:submit="submitFilters">
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2 text-gray-400">
                    <x-filament::icon icon="heroicon-o-funnel" class="h-5 w-5" />
                    <span>Filters</span>
                </div>
            </x-slot>

            {{ $this->form }}

            <x-slot name="footer">
                <div class="flex justify-end gap-3">
                    <x-filament::button color="gray" wire:click="resetFilters">
                        Clear Filters
                    </x-filament::button>
                    <x-filament::button type="submit">
                        Apply Filters
                    </x-filament::button>
                </div>
            </x-slot>
        </x-filament::section>
    </form>

    {{-- Summary Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <x-filament::section>
            <div class="flex justify-between items-center text-gray-500 text-xs mb-1">
                <span>TOTAL CAMPAIGNS</span>
                <x-filament::icon icon="heroicon-o-megaphone" class="h-4 w-4" />
            </div>
            <div class="text-2xl font-bold">{{ number_format($summary['total_campaigns']) }}</div>
            <div class="text-xs text-success-600 mt-2 font-medium">{{ number_format($summary['active_count']) }} active</div>
        </x-filament::section>

        <x-filament::section>
            <div class="flex justify-between items-center text-gray-500 text-xs mb-1">
                <span>TOTAL RAISED</span>
                <x-filament::icon icon="heroicon-o-banknotes" class="h-4 w-4" />
            </div>
            <div class="text-2xl font-bold">${{ number_format($summary['total_raised'], 2) }}</div>
            <div class="text-xs text-gray-500 mt-2 font-medium">{{ number_format($summary['overall_progress'], 1) }}% of total goal</div>
        </x-filament::section>

        <x-filament::section>
            <div class="flex justify-between items-center text-gray-500 text-xs mb-1">
                <span>TOTAL DONORS</span>
                <x-filament::icon icon="heroicon-o-user-group" class="h-4 w-4" />
            </div>
            <div class="text-2xl font-bold">{{ number_format($summary['total_donors']) }}</div>
            <div class="text-xs text-gray-500 mt-2 font-medium">{{ number_format($summary['total_donations']) }} donations</div>
        </x-filament::section>

        <x-filament::section>
            <div class="flex justify-between items-center text-gray-500 text-xs mb-1">
                <span>AVG DONATION</span>
                <x-filament::icon icon="heroicon-o-chart-pie" class="h-4 w-4" />
            </div>
            <div class="text-2xl font-bold">${{ number_format($summary['avg_donation'], 2) }}</div>
            <div class="text-xs text-gray-500 mt-2 font-medium">Average per donation</div>
        </x-filament::section>
    </div>

    {{-- Performance Overview --}}
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-filament::icon icon="heroicon-o-bolt" class="h-5 w-5 text-gray-400" />
                <span>Performance Overview</span>
            </div>
        </x-slot>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach(['top_campaign' => ['Success', 'heroicon-m-trophy', 'Total Raised'], 'most_engaging' => ['Engagement', 'heroicon-m-heart', 'Donors'], 'best_performing' => ['Performance', 'heroicon-m-sparkles', 'Goal Progress']] as $key => $meta)
                @if($insights[$key])
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-{{ ['Success' => 'success', 'Engagement' => 'info', 'Performance' => 'warning'][$meta[0]] }}-100 rounded-full h-12 w-12 flex items-center justify-center">
                            <x-filament::icon icon="{{ $meta[1] }}" class="h-6 w-6 text-{{ ['Success' => 'success', 'Engagement' => 'info', 'Performance' => 'warning'][$meta[0]] }}-600" />
                        </div>
                        <div>
                            <div class="text-xs font-bold text-gray-500 uppercase">{{ $meta[0] }}</div>
                            <div class="font-bold text-gray-950 dark:text-white truncate max-w-[150px]">{{ $insights[$key]['title'] }}</div>
                            <div class="text-sm text-gray-500">
                                {{ $meta[2] }}: 
                                @if($key === 'top_campaign') ${{ number_format($insights[$key]['total_raised'], 2) }}
                                @elseif($key === 'most_engaging') {{ number_format($insights[$key]['donor_count']) }}
                                @else {{ number_format($insights[$key]['progress_percentage'], 1) }}%
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </x-filament::section>

    {{-- Campaigns List --}}
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-filament::icon icon="heroicon-o-list-bullet" class="h-5 w-5 text-gray-400" />
                <span>Campaigns List</span>
            </div>
        </x-slot>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm divide-y divide-gray-200 dark:divide-white/10">
                <thead>
                    <tr class="bg-gray-50 dark:bg-white/5">
                        <th class="px-4 py-3 font-semibold">Campaign</th>
                        <th class="px-4 py-3 font-semibold text-center">Status</th>
                        <th class="px-4 py-3 font-semibold text-right">Goal</th>
                        <th class="px-4 py-3 font-semibold text-right">Raised</th>
                        <th class="px-4 py-3 font-semibold">Progress</th>
                        <th class="px-4 py-3 font-semibold text-center">Avg Donation</th>
                        <th class="px-4 py-3 font-semibold text-center">Duration</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                    @forelse($campaigns as $campaign)
                        <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                            <td class="px-4 py-4 flex items-center gap-3">
                                <div class="h-8 w-8 rounded-full bg-gray-100 dark:bg-white/5 flex items-center justify-center">
                                    <x-filament::icon icon="heroicon-m-megaphone" class="h-4 w-4 text-primary-600" />
                                </div>
                                <div>
                                    <div class="font-bold text-gray-950 dark:text-white">{{ $campaign['title'] }}</div>
                                    <div class="text-xs text-gray-500 font-medium">
                                        {{ \Carbon\Carbon::parse($campaign['start_date'])->format('M d, Y') }} - 
                                        {{ $campaign['end_date'] ? \Carbon\Carbon::parse($campaign['end_date'])->format('M d, Y') : 'Ongoing' }}
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-center">
                                <x-filament::badge :color="match($campaign['status']) { 'active' => 'success', 'completed' => 'primary', 'draft' => 'gray', 'upcoming' => 'info', default => 'warning' }">
                                    {{ ucfirst($campaign['status']) }}
                                </x-filament::badge>
                            </td>
                            <td class="px-4 py-4 text-right font-medium">${{ number_format($campaign['goal_amount'], 2) }}</td>
                            <td class="px-4 py-4 text-right">
                                <div class="font-bold text-success-600 dark:text-success-400">${{ number_format($campaign['total_raised'], 2) }}</div>
                                <div class="text-xs text-gray-500 font-medium">{{ number_format($campaign['progress_percentage'], 1) }}% of goal</div>
                            </td>
                            <td class="px-4 py-4 w-32">
                                <div class="flex items-center gap-2">
                                    <div class="flex-grow bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                                        <div class="h-1.5 rounded-full bg-gray-400" style="width: {{ min(100, $campaign['progress_percentage']) }}%; background-color: {{ $campaign['progress_percentage'] >= 100 ? '#10b981' : '#3b82f6' }}"></div>
                                    </div>
                                    <span class="text-xs text-gray-500">{{ number_format($campaign['progress_percentage'], 0) }}%</span>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-center">
                                <div class="font-bold text-gray-950 dark:text-white">${{ number_format($campaign['donation_count'] > 0 ? $campaign['total_raised'] / $campaign['donation_count'] : 0, 2) }}</div>
                                <div class="text-xs text-gray-500 font-medium">Per donation</div>
                            </td>
                            <td class="px-4 py-4 text-center text-gray-500 font-medium">
                                @if($campaign['start_date'] && $campaign['end_date'])
                                    {{ \Carbon\Carbon::parse($campaign['end_date'])->diffInDays(\Carbon\Carbon::parse($campaign['start_date'])) }} days
                                @else
                                    Ongoing
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">No campaigns found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>

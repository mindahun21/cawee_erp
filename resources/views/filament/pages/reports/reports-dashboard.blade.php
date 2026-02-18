<x-filament-panels::page>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Recent Donations --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-filament::icon
                        icon="heroicon-o-clock"
                        class="h-5 w-5 text-gray-400"
                    />
                    <span>Recent Donations</span>
                </div>
            </x-slot>
            
            <x-slot name="headerEnd">
                <x-filament::button
                    href="/admin/donations"
                    tag="a"
                    color="gray"
                    size="sm"
                >
                    View All
                </x-filament::button>
            </x-slot>

            <div class="divide-y divide-gray-200 dark:divide-white/10">
                @foreach($recentDonations as $donation)
                    <div class="py-3 flex justify-between items-center">
                        <div>
                            <div class="font-medium text-sm text-gray-950 dark:text-white">
                                {{ $donation['first_name'] }} {{ $donation['last_name'] }}
                            </div>
                            <div class="text-xs text-gray-500 flex items-center gap-2 mt-1">
                                <span>{{ \Carbon\Carbon::parse($donation['donation_date'])->format('M d, Y') }}</span>
                                <span>•</span>
                                <span>{{ $donation['campaign_title'] }}</span>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="font-bold text-success-600 dark:text-success-400">
                                ${{ number_format($donation['amount'], 2) }}
                            </div>
                            <div class="text-xs text-gray-500 flex items-center justify-end gap-1 mt-1">
                                @if($donation['is_recurring'])
                                    <x-filament::icon icon="heroicon-m-arrow-path" class="h-3 w-3 text-primary-500" />
                                    <span>Recurring</span>
                                @else
                                    <span>One-time</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        {{-- Top Campaigns --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-filament::icon
                        icon="heroicon-o-trophy"
                        class="h-5 w-5 text-gray-400"
                    />
                    <span>Top Performing Campaigns</span>
                </div>
            </x-slot>

            <div class="divide-y divide-gray-200 dark:divide-white/10">
                @foreach($topCampaigns as $campaign)
                    <div class="py-3">
                        <div class="flex justify-between items-center mb-1">
                            <span class="font-medium text-sm text-gray-950 dark:text-white">{{ $campaign['title'] }}</span>
                            <span class="text-sm font-bold text-primary-600">{{ number_format($campaign['progress'], 1) }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 mb-1">
                            <div class="bg-gray-400 h-1.5 rounded-full" style="width: {{ min(100, $campaign['progress']) }}%; background-color: {{ $campaign['progress'] >= 100 ? '#10b981' : '#3b82f6' }}"></div>
                        </div>
                        <div class="flex justify-between text-xs text-gray-500">
                            <span>${{ number_format($campaign['total_raised'], 2) }} raised</span>
                            <span>{{ $campaign['donor_count'] }} donors</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>

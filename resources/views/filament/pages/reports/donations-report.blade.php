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
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <x-filament::section>
            <div class="flex items-center gap-4">
                <div class="p-2 bg-success-50 dark:bg-success-900/10 rounded-lg">
                    <x-filament::icon icon="heroicon-o-banknotes" class="w-6 h-6 text-success-600" />
                </div>
                <div>
                    <div class="text-xs text-gray-500 font-medium uppercase tracking-wider">Total Amount</div>
                    <div class="text-2xl font-bold">${{ number_format($summary['total_amount'], 2) }}</div>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="flex items-center gap-4">
                <div class="p-2 bg-primary-50 dark:bg-primary-900/10 rounded-lg">
                    <x-filament::icon icon="heroicon-o-hashtag" class="w-6 h-6 text-primary-600" />
                </div>
                <div>
                    <div class="text-xs text-gray-500 font-medium uppercase tracking-wider">Donation Count</div>
                    <div class="text-2xl font-bold">{{ number_format($summary['count']) }}</div>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="flex items-center gap-4">
                <div class="p-2 bg-info-50 dark:bg-info-900/10 rounded-lg">
                    <x-filament::icon icon="heroicon-o-calculator" class="w-6 h-6 text-info-600" />
                </div>
                <div>
                    <div class="text-xs text-gray-500 font-medium uppercase tracking-wider">Average Donation</div>
                    <div class="text-2xl font-bold">${{ number_format($summary['avg_amount'], 2) }}</div>
                </div>
            </div>
        </x-filament::section>
    </div>

    {{-- Donations Table --}}
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-filament::icon icon="heroicon-o-list-bullet" class="h-5 w-5 text-gray-400" />
                <span>Transaction Listing</span>
            </div>
        </x-slot>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm divide-y divide-gray-200 dark:divide-white/10">
                <thead>
                    <tr class="bg-gray-50 dark:bg-white/5">
                        <th class="px-4 py-3 font-semibold">Date</th>
                        <th class="px-4 py-3 font-semibold">Donor</th>
                        <th class="px-4 py-3 font-semibold">Campaign</th>
                        <th class="px-4 py-3 font-semibold text-right">Amount</th>
                        <th class="px-4 py-3 font-semibold">Type</th>
                        <th class="px-4 py-3 font-semibold text-center">Frequency</th>
                        <th class="px-4 py-3 font-semibold">Method</th>
                        <th class="px-4 py-3 font-semibold text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                    @forelse($donations as $donation)
                        <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                            <td class="px-4 py-4 whitespace-nowrap text-gray-950 dark:text-white font-medium">
                                {{ \Carbon\Carbon::parse($donation['donation_date'])->format('M d, Y') }}
                            </td>
                            <td class="px-4 py-4">
                                <div class="font-bold text-gray-950 dark:text-white">{{ $donation['donor_name'] }}</div>
                                <div class="text-xs text-gray-500">{{ $donation['donor_email'] }} • {{ ucfirst($donation['donor_type']) }}</div>
                            </td>
                            <td class="px-4 py-4">
                                <span class="text-gray-900 dark:text-gray-100">{{ $donation['campaign_title'] }}</span>
                            </td>
                            <td class="px-4 py-4 text-right">
                                <span class="font-bold text-success-600 dark:text-success-400">${{ number_format($donation['amount'], 2) }}</span>
                                <span class="text-xs text-gray-500 ml-1">{{ $donation['currency_code'] }}</span>
                            </td>
                            <td class="px-4 py-4">
                                <x-filament::badge color="gray">{{ $donation['donation_type'] }}</x-filament::badge>
                            </td>
                            <td class="px-4 py-4 text-center">
                                @if($donation['is_recurring'])
                                    <x-filament::badge color="primary">Recurring</x-filament::badge>
                                @else
                                    <x-filament::badge color="gray" icon="heroicon-o-bolt-slash">One-time</x-filament::badge>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-gray-500 italic">{{ $donation['payment_method'] ?: 'N/A' }}</td>
                            <td class="px-4 py-4 text-center">
                                <x-filament::badge :color="match($donation['status']) { 'completed' => 'success', 'pending' => 'warning', 'failed' => 'danger', default => 'gray' }">
                                    {{ ucfirst($donation['status']) }}
                                </x-filament::badge>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-gray-500">No donations found matching filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>

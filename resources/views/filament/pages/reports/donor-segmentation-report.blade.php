<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-6">
        <div class="p-6 bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700">
            <div class="mb-6">
                {{ $this->form }}
            </div>
            
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-4">
                Segmentation allows you to group your donors based on various criteria to better understand your donor base and tailor your fundraising efforts.
            </p>
        </div>

        @php
            $service = app(\App\Services\ReportService::class);
            $segments = $service->getDonorSegmentation($data['segmentBy'] ?? 'category');
        @endphp

        <div class="overflow-hidden bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 font-semibold text-gray-900 dark:text-white text-left">Segment</th>
                        <th class="px-6 py-3 font-semibold text-gray-900 dark:text-white text-center">Donor Count</th>
                        <th class="px-6 py-3 font-semibold text-gray-900 dark:text-white text-center">Donation Count</th>
                        <th class="px-6 py-3 font-semibold text-gray-900 dark:text-white text-right">Total Amount</th>
                        <th class="px-6 py-3 font-semibold text-gray-900 dark:text-white text-right">Avg Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-600">
                    @foreach($segments as $segment)
                    <tr>
                        <td class="px-6 py-4 text-gray-700 dark:text-gray-300 font-medium">
                            {{ $segment['segment'] }}
                        </td>
                        <td class="px-6 py-4 text-gray-700 dark:text-gray-300 text-center">
                            {{ number_format($segment['donor_count']) }}
                        </td>
                        <td class="px-6 py-4 text-gray-700 dark:text-gray-300 text-center">
                            {{ number_format($segment['donation_count'] ?? 0) }}
                        </td>
                        <td class="px-6 py-4 text-gray-700 dark:text-gray-300 text-right font-semibold">
                            {{ number_format($segment['total_amount'], 2) }}
                        </td>
                        <td class="px-6 py-4 text-gray-700 dark:text-gray-300 text-right">
                            {{ number_format($segment['avg_amount'], 2) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>

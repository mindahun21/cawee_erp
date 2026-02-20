<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-6">
        <div class="p-6 bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700">
            <h2 class="text-lg font-bold mb-4">Forecasting Methodology</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                This forecast is based on current active recurring donations. It assumes all recurring donations will continue as scheduled for the next 12 months.
            </p>
        </div>
        
        @php
            $service = app(\App\Services\ReportService::class);
            $forecast = $service->getRecurringDonationForecast();
        @endphp

        <div class="overflow-hidden bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 font-semibold text-gray-900 dark:text-white">Month</th>
                        <th class="px-6 py-3 font-semibold text-gray-900 dark:text-white">Expected Donations</th>
                        <th class="px-6 py-3 font-semibold text-gray-900 dark:text-white text-right">Expected Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-600">
                    @foreach($forecast as $data)
                    <tr>
                        <td class="px-6 py-4 text-gray-700 dark:text-gray-300 font-medium">{{ $data['month'] }}</td>
                        <td class="px-6 py-4 text-gray-700 dark:text-gray-300">{{ number_format($data['donation_count']) }}</td>
                        <td class="px-6 py-4 text-gray-700 dark:text-gray-300 text-right font-semibold">{{ number_format($data['expected_amount'], 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>

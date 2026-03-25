{{-- resources/views/filament/leave/balance-report-table.blade.php --}}
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Hire Date:
                <strong>{{ $record->date_of_employment?->format('d M Y') ?? '—' }}</strong>
            </p>
        </div>
        <div>
            @php
                $totalAvailable = $report->last()['remaining_balance'] ?? 0;
            @endphp
            <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-semibold
                {{ $totalAvailable > 10 ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' :
                   ($totalAvailable > 0 ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' :
                   'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200') }}">
                Current Balance: {{ $totalAvailable }} days
            </span>
        </div>
    </div>

    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Period</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">From</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">To</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Entitlement</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Taken (FIFO)</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Remaining</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($report as $period)
                    @php
                    $rowClass = $period['is_current_period']
                        ? 'bg-blue-50 dark:bg-blue-900/30 border-l-4 border-blue-500'
                        : 'hover:bg-gray-50 dark:hover:bg-gray-800/50';
                    $textClass = $period['is_current_period']
                        ? 'text-blue-900 dark:text-blue-100 font-semibold'
                        : 'text-gray-700 dark:text-gray-300';
                @endphp
                <tr class="{{ $rowClass }}">
                    <td class="px-4 py-3 whitespace-nowrap {{ $textClass }}">
                        {{ $period['period_label'] }}
                        @if($period['is_current_period'])
                            <span class="ml-1 inline-flex items-center rounded-full bg-blue-500 px-1.5 py-0.5 text-xs font-medium text-white">current</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap {{ $textClass }}">
                        {{ $period['from_date']->format('d M Y') }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap {{ $textClass }}">
                        {{ $period['to_date']->format('d M Y') }}
                    </td>
                    <td class="px-4 py-3 text-center {{ $textClass }}">
                        <span title="Full entitlement: {{ $period['full_annual_leave_balance'] }} days">
                            {{ $period['annual_leave_balance'] }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center {{ $textClass }}">
                        @if($period['actual_leaves_taken'] !== $period['leaves_taken'])
                            <span class="line-through text-gray-400 mr-1 text-xs">{{ $period['actual_leaves_taken'] }}</span>
                        @endif
                        {{ $period['leaves_taken'] }}
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-flex items-center justify-center rounded-full px-2.5 py-0.5 text-xs font-semibold
                            {{ $period['remaining_balance'] > 10 ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100' :
                               ($period['remaining_balance'] > 0 ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100' :
                               'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100') }}">
                            {{ $period['remaining_balance'] }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($period['is_current_period'])
                            <span class="inline-flex items-center rounded-full bg-blue-500 px-2 py-0.5 text-xs font-medium text-white">
                                Active
                            </span>
                        @else
                            <span class="text-xs text-gray-400 dark:text-gray-500">Historical</span>
                        @endif
                    </td>
                </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-400">
                            No leave balance data found. Ensure the employee has a hire date and an Annual Leave type is configured.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if($report->count() > 0)
            <tfoot class="bg-gray-50 dark:bg-gray-800 border-t-2 border-gray-300 dark:border-gray-600">
                <tr>
                    <td colspan="3" class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Total</td>
                    <td class="px-4 py-3 text-center font-bold">{{ $report->sum('annual_leave_balance') }}</td>
                    <td class="px-4 py-3 text-center font-bold">{{ $report->sum('actual_leaves_taken') }}</td>
                    <td class="px-4 py-3 text-center font-bold text-primary-600 dark:text-primary-400">
                        {{ $report->last()['remaining_balance'] ?? 0 }}
                    </td>
                    <td></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>

    <p class="text-xs text-gray-400 mt-2">
        * "Taken (FIFO)" shows leave deducted from the oldest available balance first. Strikethrough = original raw value.
    </p>
</div>

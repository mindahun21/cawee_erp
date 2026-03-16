<div id="cost-by-item-report" class="rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-white/5 shadow-sm overflow-hidden">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 dark:border-white/10 px-4 py-3 sm:px-6">
        <div>
            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Cost by Item</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">Aggregated spend by product/description.</p>
        </div>
        <div class="text-xs text-gray-600 dark:text-gray-400">
            <span><strong>{{ number_format($costByItem->count()) }}</strong> items</span>
        </div>
    </div>
    <div class="fi-ta">
        <div class="fi-ta-main">
            <div class="fi-ta-content-ctn fi-fixed-positioning-context">
                <div class="fi-ta-content overflow-x-auto">
                    <table class="fi-ta-table">
                <thead>
                    <tr>
                        <th class="fi-ta-header-cell">Description</th>
                        <th class="fi-ta-header-cell">Unit</th>
                        <th class="fi-ta-header-cell fi-align-end">Lines</th>
                        <th class="fi-ta-header-cell fi-align-end">Quantity</th>
                        <th class="fi-ta-header-cell fi-align-end">Total Cost</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($costByItem as $row)
                        <tr class="fi-ta-record">
                            <td class="fi-ta-cell">{{ $row['description'] ?? '—' }}</td>
                            <td class="fi-ta-cell">{{ $row['unit'] ?? '—' }}</td>
                            <td class="fi-ta-cell fi-align-end">{{ number_format($row['lines']) }}</td>
                            <td class="fi-ta-cell fi-align-end">{{ number_format($row['quantity'], 2) }}</td>
                            <td class="fi-ta-cell fi-align-end">{{ number_format($row['total'], 2) }}</td>
                        </tr>
                    @empty
                        <tr class="fi-ta-record">
                            <td colspan="5" class="fi-ta-cell fi-align-center">No item data for the selected filters.</td>
                        </tr>
                    @endforelse
                </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

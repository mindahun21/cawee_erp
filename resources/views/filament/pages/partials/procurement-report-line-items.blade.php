<div id="line-items-report" class="rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-white/5 shadow-sm overflow-hidden">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 dark:border-white/10 px-4 py-3 sm:px-6">
        <div>
            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Purchase Order Line Items</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">Line-level breakdown of purchase orders by product/description.</p>
        </div>
        <div class="flex items-center gap-4 text-xs text-gray-600 dark:text-gray-400">
            <span><strong>{{ number_format($itemsSummary['lines']) }}</strong> lines</span>
            <span>Qty: <strong>{{ number_format($itemsSummary['quantity'], 2) }}</strong></span>
            <span>Total: <strong>{{ number_format($itemsSummary['lineTotal'], 2) }}</strong></span>
        </div>
    </div>
    <div class="fi-ta">
        <div class="fi-ta-main">
            <div class="fi-ta-content-ctn fi-fixed-positioning-context">
                <div class="fi-ta-content overflow-x-auto">
                    <table class="fi-ta-table">
                <thead>
                    <tr>
                        <th class="fi-ta-header-cell">PO #</th>
                        <th class="fi-ta-header-cell">Description</th>
                        <th class="fi-ta-header-cell">Unit</th>
                        <th class="fi-ta-header-cell fi-align-end">Quantity</th>
                        <th class="fi-ta-header-cell fi-align-end">Unit Price</th>
                        <th class="fi-ta-header-cell fi-align-end">Tax %</th>
                        <th class="fi-ta-header-cell fi-align-end">Line Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr class="fi-ta-record">
                            <td class="fi-ta-cell">{{ optional($item->purchaseOrder)->po_number ?? '—' }}</td>
                            <td class="fi-ta-cell">{{ $item->description ?? '—' }}</td>
                            <td class="fi-ta-cell">{{ $item->unit ?? '—' }}</td>
                            <td class="fi-ta-cell fi-align-end">{{ number_format($item->quantity ?? 0, 2) }}</td>
                            <td class="fi-ta-cell fi-align-end">{{ number_format($item->unit_price ?? 0, 2) }}</td>
                            <td class="fi-ta-cell fi-align-end">{{ number_format($item->tax_rate ?? 0, 2) }}</td>
                            <td class="fi-ta-cell fi-align-end">{{ number_format($item->line_total ?? 0, 2) }}</td>
                        </tr>
                    @empty
                        <tr class="fi-ta-record">
                            <td colspan="7" class="fi-ta-cell fi-align-center">No line items found for the selected filters.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if($items->isNotEmpty())
                    <tfoot>
                        <tr class="fi-ta-record">
                            <th colspan="3" class="fi-ta-cell fi-align-end">Totals</th>
                            <th class="fi-ta-cell fi-align-end">{{ number_format($itemsSummary['quantity'], 2) }}</th>
                            <th class="fi-ta-cell"></th>
                            <th class="fi-ta-cell"></th>
                            <th class="fi-ta-cell fi-align-end">{{ number_format($itemsSummary['lineTotal'], 2) }}</th>
                        </tr>
                    </tfoot>
                @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="requisitions-report" class="rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-white/5 shadow-sm overflow-hidden">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 dark:border-white/10 px-4 py-3 sm:px-6">
        <div>
            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Requisitions Report</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">Purchase requisitions in the selected period.</p>
        </div>
        <div class="flex items-center gap-4 text-xs text-gray-600 dark:text-gray-400">
            <span><strong>{{ number_format($reqSummary['count']) }}</strong> requisitions</span>
            <span>Est. total: <strong>{{ number_format($reqSummary['total'], 2) }}</strong></span>
        </div>
    </div>
    <div class="fi-ta">
        <div class="fi-ta-main">
            <div class="fi-ta-content-ctn fi-fixed-positioning-context">
                <div class="fi-ta-content overflow-x-auto">
                    <table class="fi-ta-table">
                <thead>
                    <tr>
                        <th class="fi-ta-header-cell">Requisition #</th>
                        <th class="fi-ta-header-cell">Category</th>
                        <th class="fi-ta-header-cell">Requested By</th>
                        <th class="fi-ta-header-cell">Status</th>
                        <th class="fi-ta-header-cell fi-align-end">Est. Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requisitions as $req)
                        <tr class="fi-ta-record">
                            <td class="fi-ta-cell">{{ $req->requisition_number ?? '—' }}</td>
                            <td class="fi-ta-cell">{{ $req->category ?? '—' }}</td>
                            <td class="fi-ta-cell">{{ optional($req->requester)->name ?? '—' }}</td>
                            <td class="fi-ta-cell">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-medium bg-gray-500/10 text-gray-600 dark:text-gray-400">{{ $req->overall_status ?? '—' }}</span>
                            </td>
                            <td class="fi-ta-cell fi-align-end">{{ number_format($req->estimated_total ?? 0, 2) }}</td>
                        </tr>
                    @empty
                        <tr class="fi-ta-record">
                            <td colspan="5" class="fi-ta-cell fi-align-center">No requisitions found for the selected filters.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if($requisitions->isNotEmpty())
                    <tfoot>
                        <tr class="fi-ta-record">
                            <th colspan="4" class="fi-ta-cell fi-align-end">Totals</th>
                            <th class="fi-ta-cell fi-align-end">{{ number_format($reqSummary['total'], 2) }}</th>
                        </tr>
                    </tfoot>
                @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

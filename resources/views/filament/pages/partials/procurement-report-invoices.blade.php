<div id="invoice-report" class="rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-white/5 shadow-sm overflow-hidden">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 dark:border-white/10 px-4 py-3 sm:px-6">
        <div>
            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Purchase Invoices Report</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">High-level view of purchase invoices by period and currency.</p>
        </div>
        <div class="flex items-center gap-4 text-xs text-gray-600 dark:text-gray-400">
            <span><strong>{{ number_format($invoiceSummary['count']) }}</strong> invoices</span>
            <span>Subtotal: <strong>{{ number_format($invoiceSummary['subtotal'], 2) }}</strong></span>
            <span>Tax: <strong>{{ number_format($invoiceSummary['tax'], 2) }}</strong></span>
            <span>Total: <strong>{{ number_format($invoiceSummary['total'], 2) }}</strong></span>
        </div>
    </div>
    <div class="fi-ta">
        <div class="fi-ta-main">
            <div class="fi-ta-content-ctn fi-fixed-positioning-context">
                <div class="fi-ta-content overflow-x-auto">
                    <table class="fi-ta-table">
                        <thead>
                            <tr>
                                <th class="fi-ta-header-cell">Invoice #</th>
                                <th class="fi-ta-header-cell">Contract</th>
                                <th class="fi-ta-header-cell">PO</th>
                                <th class="fi-ta-header-cell">Supplier</th>
                                <th class="fi-ta-header-cell">Invoice Date</th>
                                <th class="fi-ta-header-cell">Status</th>
                                <th class="fi-ta-header-cell fi-align-end">Subtotal</th>
                                <th class="fi-ta-header-cell fi-align-end">Tax</th>
                                <th class="fi-ta-header-cell fi-align-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($invoices as $invoice)
                                <tr class="fi-ta-record">
                                    <td class="fi-ta-cell">{{ $invoice->invoice_number ?? '—' }}</td>
                                    <td class="fi-ta-cell">{{ optional(optional($invoice->purchaseOrder)->contract)->contract_number ?? '—' }}</td>
                                    <td class="fi-ta-cell">{{ optional($invoice->purchaseOrder)->po_number ?? '—' }}</td>
                                    <td class="fi-ta-cell">{{ optional($invoice->supplier)->name ?? '—' }}</td>
                                    <td class="fi-ta-cell">{{ $invoice->invoice_date?->format('Y-m-d') ?? '—' }}</td>
                                    <td class="fi-ta-cell">
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-medium
                                            @class([
                                                'bg-success-500/10 text-success-600 dark:text-success-400' => $invoice->status === \App\Models\Procurement\Invoice::STATUS_PAID,
                                                'bg-warning-500/10 text-warning-600 dark:text-warning-400' => in_array($invoice->status, ['Approved', 'Submitted', 'Matched']),
                                                'bg-danger-500/10 text-danger-600 dark:text-danger-400' => in_array($invoice->status, ['Rejected', 'Disputed']),
                                                'bg-gray-500/10 text-gray-600 dark:text-gray-400' => ! in_array($invoice->status, [\App\Models\Procurement\Invoice::STATUS_PAID, 'Approved', 'Submitted', 'Matched', 'Rejected', 'Disputed']),
                                            ])">
                                            {{ $invoice->status ?? '—' }}
                                        </span>
                                    </td>
                                    <td class="fi-ta-cell fi-align-end">{{ number_format($invoice->subtotal ?? 0, 2) }}</td>
                                    <td class="fi-ta-cell fi-align-end">{{ number_format($invoice->tax_amount ?? 0, 2) }}</td>
                                    <td class="fi-ta-cell fi-align-end">{{ number_format($invoice->total_amount ?? 0, 2) }}</td>
                                </tr>
                            @empty
                                <tr class="fi-ta-record">
                                    <td colspan="9" class="fi-ta-cell fi-align-center">No invoices found for the selected filters.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($invoices->isNotEmpty())
                            <tfoot>
                                <tr class="fi-ta-record">
                                    <th colspan="6" class="fi-ta-cell fi-align-end">Totals</th>
                                    <th class="fi-ta-cell fi-align-end">{{ number_format($invoiceSummary['subtotal'], 2) }}</th>
                                    <th class="fi-ta-cell fi-align-end">{{ number_format($invoiceSummary['tax'], 2) }}</th>
                                    <th class="fi-ta-cell fi-align-end">{{ number_format($invoiceSummary['total'], 2) }}</th>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

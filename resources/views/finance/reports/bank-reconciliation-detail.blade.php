<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Reconciliation Detail - {{ $record->reference }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            body { font-size: 11pt; color: #000; }
            .no-print { display: none !important; }
            .page-break { page-break-before: always; }
        }
        body { background: #f3f4f6; padding: 2rem; }
        .report-container { max-width: 900px; margin: 0 auto; background: #fff; padding: 3rem; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); }
        .data-table { width: 100%; text-align: left; border-collapse: collapse; margin-bottom: 1rem; }
        .data-table th, .data-table td { padding: 0.25rem 0; }
        .data-table th { padding-bottom: 0.5rem; font-weight: 600; border-bottom: 1px solid #d1d5db; }
        .section-header { font-weight: 600; padding-top: 1rem; }
        .sub-section { padding-left: 1.5rem; font-style: italic; color: #4b5563; }
        .item-row td { padding-left: 3rem; font-size: 0.9em; }
        .item-row .text-right { text-align: right; font-family: monospace; }
        .total-row td { border-top: 1px dotted #9ca3af; font-weight: 600; padding-top: 0.5rem; text-align: right; font-family: monospace;}
        .main-total-row td { border-top: 1px solid #111827; font-weight: bold; font-family: monospace; text-align: right; padding-top: 0.5rem; padding-bottom: 0.5rem;}
        .main-total-row td:first-child { text-align: left; font-family: inherit; }
    </style>
</head>
<body>
    <div class="mb-4 text-center no-print">
        <button onclick="window.print()" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Print Report</button>
        <button onclick="window.close()" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600 ml-2">Close</button>
    </div>

    <div class="report-container">
        <!-- Header -->
        <div class="text-center mb-8 border-b pb-6">
            <h1 class="text-2xl font-bold uppercase tracking-wider text-gray-900">{{ $companyName }}</h1>
            <h2 class="text-xl font-semibold text-gray-700 mt-2">Bank Reconciliation Detail</h2>
            <p class="text-gray-500 mt-1">
                {{ $record->reference }}, Period Ending {{ $record->period?->end_date?->format('Y-m-d') ?? '—' }}
            </p>
        </div>

        @php
            $clearedCheques = $record->items->whereIn('item_type', ['payment', 'bank_charge', 'interest', 'other'])->where('is_cleared', true);
            $clearedDeposits = $record->items->where('item_type', 'deposit')->where('is_cleared', true);
            $unclearedCheques = $record->items->whereIn('item_type', ['payment', 'bank_charge', 'interest', 'other'])->where('is_cleared', false);
            $unclearedDeposits = $record->items->where('item_type', 'deposit')->where('is_cleared', false);

            $sumClearedCheques = $clearedCheques->sum('amount');
            $sumClearedDeposits = $clearedDeposits->sum('amount');
            $sumUnclearedCheques = $unclearedCheques->sum('amount');
            $sumUnclearedDeposits = $unclearedDeposits->sum('amount');
        @endphp

        <!-- Key Information -->
        <div class="mb-8 grid grid-cols-2 gap-4 text-sm">
            <div>
                <p><span class="font-semibold">Bank Account:</span> {{ $record->bankAccount?->bank_name }} - {{ $record->bankAccount?->account_number }}</p>
                <p><span class="font-semibold">Account Name:</span> {{ $record->bankAccount?->account_name }}</p>
            </div>
            <div class="text-right">
                <p><span class="font-semibold">Statement Ending Date:</span> {{ $record->statement_date?->format('Y-m-d') }}</p>
            </div>
        </div>

        <table class="data-table text-sm">
            <thead>
                <tr>
                    <th style="width: 15%">Date</th>
                    <th style="width: 20%">Type</th>
                    <th style="width: 35%">Num / Reference</th>
                    <th style="width: 15%" class="text-right">Amount</th>
                    <th style="width: 15%" class="text-right">Balance</th>
                </tr>
            </thead>
            <tbody>
                <!-- Beginning Balance -->
                <tr class="main-total-row border-b border-gray-200">
                    <td colspan="4">Beginning Balance</td>
                    <td>{{ number_format($record->gl_balance, 2) }}</td>
                </tr>

                <!-- Cleared Transactions -->
                <tr><td colspan="5" class="section-header">Cleared Transactions</td></tr>
                
                <!-- Cleared Cheques -->
                <tr><td colspan="5" class="sub-section">Checks and Payments - {{ $clearedCheques->count() }}</td></tr>
                @foreach($clearedCheques as $item)
                <tr class="item-row">
                    <td>{{ $item->transaction_date?->format('Y-m-d') }}</td>
                    <td>{{ ucfirst($item->item_type) }}</td>
                    <td>{{ $item->bank_reference ?? $item->description }}</td>
                    <td class="text-right">{{ number_format($item->amount, 2) }}</td>
                    <td></td>
                </tr>
                @endforeach

                <!-- Cleared Deposits -->
                <tr><td colspan="5" class="sub-section pt-2">Deposits and Credits - {{ $clearedDeposits->count() }}</td></tr>
                @foreach($clearedDeposits as $item)
                <tr class="item-row">
                    <td>{{ $item->transaction_date?->format('Y-m-d') }}</td>
                    <td>{{ ucfirst($item->item_type) }}</td>
                    <td>{{ $item->bank_reference ?? $item->description }}</td>
                    <td class="text-right">{{ number_format($item->amount, 2) }}</td>
                    <td></td>
                </tr>
                @endforeach

                <!-- Total Cleared -->
                <tr class="total-row border-b border-gray-200 pb-2">
                    <td colspan="3" style="text-align: left; font-family: inherit; padding-left: 1.5rem">Total Cleared Transactions</td>
                    <td>{{ number_format($sumClearedDeposits - $sumClearedCheques, 2) }}</td>
                    <td></td>
                </tr>

                <!-- Cleared Balance -->
                <tr class="main-total-row border-b border-gray-200 bg-gray-50">
                    <td colspan="4">Cleared / Statement Balance</td>
                    <td>{{ number_format($record->statement_balance, 2) }}</td>
                </tr>

                <!-- Uncleared Transactions -->
                <tr><td colspan="5" class="section-header pt-4">Uncleared Transactions</td></tr>
                
                <!-- Uncleared Cheques -->
                <tr><td colspan="5" class="sub-section">Checks and Payments - {{ $unclearedCheques->count() }}</td></tr>
                @foreach($unclearedCheques as $item)
                <tr class="item-row">
                    <td>{{ $item->transaction_date?->format('Y-m-d') }}</td>
                    <td>{{ ucfirst($item->item_type) }}</td>
                    <td>{{ $item->bank_reference ?? $item->description }}</td>
                    <td class="text-right">{{ number_format($item->amount, 2) }}</td>
                    <td></td>
                </tr>
                @endforeach

                <!-- Uncleared Deposits -->
                <tr><td colspan="5" class="sub-section pt-2">Deposits and Credits - {{ $unclearedDeposits->count() }}</td></tr>
                @foreach($unclearedDeposits as $item)
                <tr class="item-row">
                    <td>{{ $item->transaction_date?->format('Y-m-d') }}</td>
                    <td>{{ ucfirst($item->item_type) }}</td>
                    <td>{{ $item->bank_reference ?? $item->description }}</td>
                    <td class="text-right">{{ number_format($item->amount, 2) }}</td>
                    <td></td>
                </tr>
                @endforeach

                <!-- Total Uncleared -->
                <tr class="total-row border-b border-gray-200 pb-2">
                    <td colspan="3" style="text-align: left; font-family: inherit; padding-left: 1.5rem">Total Uncleared Transactions</td>
                    <td>{{ number_format($sumUnclearedDeposits - $sumUnclearedCheques, 2) }}</td>
                    <td></td>
                </tr>

                <!-- Register Balance -->
                <tr class="main-total-row pt-4">
                    <td colspan="4">Register Balance as of {{ $record->statement_date?->format('Y-m-d') }}</td>
                    <td>{{ number_format($record->gl_balance, 2) }}</td>
                </tr>

                <!-- Adjusted Balance -->
                <tr class="main-total-row">
                    <td colspan="4">Adjusted Bank Balance</td>
                    <td>{{ number_format($record->adjusted_bank_balance, 2) }}</td>
                </tr>
            </tbody>
        </table>
        
    </div>
</body>
</html>

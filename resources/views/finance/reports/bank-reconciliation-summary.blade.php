<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Reconciliation Summary - {{ $record->reference }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            body { font-size: 12pt; color: #000; }
            .no-print { display: none !important; }
            .page-break { page-break-before: always; }
        }
        body { background: #f3f4f6; padding: 2rem; }
        .report-container { max-width: 800px; margin: 0 auto; background: #fff; padding: 3rem; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); }
        .data-row { display: flex; justify-content: space-between; padding: 0.25rem 0; }
        .data-label { padding-left: 2rem; color: #4b5563; }
        .data-label.parent { padding-left: 0; font-weight: 600; color: #111827; }
        .data-value { font-family: monospace; }
        .data-value.bold { font-weight: bold; }
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
            <h2 class="text-xl font-semibold text-gray-700 mt-2">Bank Reconciliation Summary</h2>
            <p class="text-gray-500 mt-1">
                {{ $record->reference }}, Period Ending {{ $record->period?->end_date?->format('Y-m-d') ?? '—' }}
            </p>
        </div>

        @php
            // Calculate item totals
            $clearedCheques = $record->items->whereIn('item_type', ['payment', 'bank_charge', 'interest', 'other'])->where('is_cleared', true);
            $clearedDeposits = $record->items->where('item_type', 'deposit')->where('is_cleared', true);
            $unclearedCheques = $record->items->whereIn('item_type', ['payment', 'bank_charge', 'interest', 'other'])->where('is_cleared', false);
            $unclearedDeposits = $record->items->where('item_type', 'deposit')->where('is_cleared', false);

            $sumClearedCheques = $clearedCheques->sum('amount');
            $sumClearedDeposits = $clearedDeposits->sum('amount');
            $sumUnclearedCheques = $unclearedCheques->sum('amount');
            $sumUnclearedDeposits = $unclearedDeposits->sum('amount');

            // According to classic reconciliation format:
            // Cleared Balance = GL Balance (or Beginning Balance) +/- Cleared Items
            // Actually, in many systems:
            // cleared_balance = statement balance.
            // Let's use the exact math from Filament (Adjusted Bank Balance approach).
            // But we will populate the lines exactly as requested.
            // Beginning Balance is treated as GL Balance for the context of matching the user's template.
        @endphp

        <!-- Key Information -->
        <div class="mb-8 grid grid-cols-2 gap-4 text-sm">
            <div>
                <p><span class="font-semibold">Bank Account:</span> {{ $record->bankAccount?->bank_name }} - {{ $record->bankAccount?->account_number }}</p>
                <p><span class="font-semibold">Account Name:</span> {{ $record->bankAccount?->account_name }}</p>
            </div>
            <div class="text-right">
                <p><span class="font-semibold">Statement Ending Date:</span> {{ $record->statement_date?->format('Y-m-d') }}</p>
                <p><span class="font-semibold">Prepared By:</span> {{ $record->preparedBy?->name ?? '—' }}</p>
            </div>
        </div>

        <!-- The Report Body -->
        <div class="text-sm">
            
            <div class="data-row border-b pb-2 mb-2">
                <div class="data-label parent">Beginning Balance (GL Focus)</div>
                <div class="data-value bold">{{ number_format($record->gl_balance, 2) }}</div>
            </div>

            <!-- Cleared Transactions -->
            <div class="mb-4">
                <div class="data-label parent">Cleared Transactions</div>
                <div class="data-row">
                    <div class="data-label">Checks and Payments - {{ $clearedCheques->count() }}</div>
                    <div class="data-value">{{ number_format($sumClearedCheques, 2) }}</div>
                </div>
                <div class="data-row">
                    <div class="data-label">Deposits and Credits - {{ $clearedDeposits->count() }}</div>
                    <div class="data-value">{{ number_format($sumClearedDeposits, 2) }}</div>
                </div>
                <div class="data-row border-t mt-1 pt-1">
                    <div class="data-label font-semibold">Total Cleared</div>
                    <div class="data-value font-semibold">{{ number_format($sumClearedDeposits - $sumClearedCheques, 2) }}</div>
                </div>
            </div>

            <div class="data-row border-b pb-2 mb-4">
                <div class="data-label parent">Cleared / Statement Balance</div>
                <div class="data-value bold">{{ number_format($record->statement_balance, 2) }}</div>
            </div>

            <!-- Uncleared Transactions -->
            <div class="mb-4">
                <div class="data-label parent">Uncleared Transactions</div>
                <div class="data-row">
                    <div class="data-label">Checks and Payments - {{ $unclearedCheques->count() }}</div>
                    <div class="data-value">{{ number_format($sumUnclearedCheques, 2) }}</div>
                </div>
                <div class="data-row">
                    <div class="data-label">Deposits and Credits - {{ $unclearedDeposits->count() }}</div>
                    <div class="data-value">{{ number_format($sumUnclearedDeposits, 2) }}</div>
                </div>
                <div class="data-row border-t mt-1 pt-1">
                    <div class="data-label font-semibold">Total Uncleared</div>
                    <div class="data-value font-semibold">{{ number_format($sumUnclearedDeposits - $sumUnclearedCheques, 2) }}</div>
                </div>
            </div>

            <div class="data-row border-t-2 border-gray-900 pt-2 mb-2">
                <div class="data-label parent">Register Balance as of {{ $record->statement_date?->format('Y-m-d') }}</div>
                <div class="data-value bold">{{ number_format($record->gl_balance, 2) }}</div>
            </div>

            <div class="data-row border-t pt-2">
                <div class="data-label parent">Adjusted Bank Balance</div>
                <div class="data-value bold">{{ number_format($record->adjusted_bank_balance, 2) }}</div>
            </div>
            
        </div>
        
    </div>
</body>
</html>

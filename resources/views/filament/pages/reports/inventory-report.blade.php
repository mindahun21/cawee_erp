<x-filament-panels::page>
    <style>
        .report-table-wrapper {
            overflow-x: auto;
            width: 100%;
            border: 1px solid rgba(229, 231, 235, 0.1);
            background: rgba(255, 255, 255, 0.02);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            position: relative;
        }
        
        .report-inner-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.825rem;
            text-align: left;
            padding: 0;
        }

        .report-inner-table th {
            padding: 1.25rem 1.5rem;
            font-weight: 800;
            border: 1px solid rgba(156, 163, 175, 0.3);
            white-space: nowrap;
            background: rgba(75, 85, 99, 0.1);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .report-inner-table td {
            padding: 1.25rem 1.5rem;
            border: 1px solid rgba(156, 163, 175, 0.2);
            transition: background-color 0.2s;
        }

        .report-inner-table tbody tr:hover td {
            background: rgba(59, 130, 246, 0.08);
        }

        .custom-scrollbar::-webkit-scrollbar { height: 8px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: rgba(0, 0, 0, 0.05); border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(var(--primary-600), 0.4); border-radius: 10px; }
        
        .dark .report-inner-table th { background: rgba(31, 41, 55, 0.95); }
        .dark .report-inner-table td { border-color: rgba(75, 85, 99, 0.4); }
        .dark .report-table-wrapper { border-color: rgba(75, 85, 99, 0.5); background: rgba(0,0,0,0.2); }
    </style>

    <div class="flex flex-col gap-32 p-12 md:p-24 mb-32">
        <x-filament::section class="mb-0 rounded-t-lg border-b-0">
            <div class="flex flex-col rounded-t-lg gap-4">
                <form wire:submit="loadReport" class="w-full mb-20">
                    <div class="mb-10">
                        {{ $this->form }}
                    </div>
                </form>

                <div class="flex flex-wrap gap-20 items-center pt-24">
                    <span class="text-xs font-black uppercase text-gray-400 tracking-[0.5em] mr-12 ml-4">Export Tools:</span>
                    <x-filament::button color="danger" icon="heroicon-m-document-arrow-down" wire:click="export('pdf')" size="lg" class="px-16 py-6 shadow-2xl">PDF Report</x-filament::button>
                    <x-filament::button color="success" icon="heroicon-m-table-cells" wire:click="export('excel')" size="lg" class="px-16 py-6 shadow-2xl">Excel Sheet</x-filament::button>
                    <x-filament::button color="gray" icon="heroicon-m-list-bullet" wire:click="export('csv')" size="lg" class="px-16 py-6 shadow-2xl">CSV File</x-filament::button>
                </div>
            </div>
        </x-filament::section>



        <div class="py-12 bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 border-b-0 border-t-0 p-6 flex flex-col gap-6">
            <x-filament::tabs label="Reports" class="overflow-x-auto custom-scrollbar">
                @foreach([
                    'valuation' => 'Asset Valuation',
                    'depreciation' => 'Depreciation',
                    'aging' => 'Asset Aging',
                    'utilization' => 'Utilization',
                    'movement' => 'Movement',
                    'damaged' => 'Lost/Damaged',
                    'location' => 'Location-wise',
                    'category' => 'Category-wise',
                    'procurement' => 'Procurement',
                    'turnover' => 'Inventory Turnover'
                ] as $key => $label)
                    <x-filament::tabs.item
                        :active="$activeTab === $key"
                        wire:click="setActiveTab('{{ $key }}')"
                        class="font-black text-[12px] uppercase tracking-[0.2em] px-12 py-6"
                    >
                        {{ $label }}
                    </x-filament::tabs.item>
                @endforeach
            </x-filament::tabs>
        </div>



        <div class="space-y-48 bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 border-t-0 rounded-b-xl p-6 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
            @if($activeTab === 'valuation')
                <div class="grid grid-cols-1 md:grid-cols-2 gap-32 mb-32">
                    <x-filament::section>
                        <p class="text-xs uppercase font-bold text-gray-500">Total Purchase Cost</p>
                        <p class="text-3xl font-black text-primary-600">Rs. {{ number_format($reportData['total_purchase_cost'], 2) }}</p>
                    </x-filament::section>
                    <x-filament::section>
                        <p class="text-xs uppercase font-bold text-gray-500">Current Market Value</p>
                        <p class="text-3xl font-black text-success-600">Rs. {{ number_format($reportData['current_market_value'], 2) }}</p>
                    </x-filament::section>
                </div>

                <div class="report-table-wrapper custom-scrollbar mt-24">
                    <table class="report-inner-table min-w-[800px]">
                        <thead>
                            <tr>
                                <th>Asset Name</th>
                                <th>Code</th>
                                <th class="text-right">Purchase Cost</th>
                                <th class="text-right">Current Value</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reportData['assets'] as $asset)
                                <tr>
                                    <td class="font-bold">{{ $asset['name'] }}</td>
                                    <td class="font-mono text-xs">{{ $asset['code'] }}</td>
                                    <td class="text-right font-mono">{{ number_format($asset['cost'], 2) }}</td>
                                    <td class="text-right font-mono font-bold text-success-600">{{ number_format($asset['current'], 2) }}</td>
                                    <td class="text-center">
                                        <x-filament::badge>{{ $asset['status'] }}</x-filament::badge>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            @elseif($activeTab === 'depreciation')
                <div class="report-table-wrapper custom-scrollbar mt-24">
                    <table class="report-inner-table min-w-[800px]">
                        <thead>
                            <tr>
                                <th>Period Date</th>
                                <th>Asset</th>
                                <th class="text-right">Depreciation</th>
                                <th class="text-right">Book Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reportData as $log)
                                <tr>
                                    <td class="font-mono">{{ $log->period_date->format('M Y') }}</td>
                                    <td class="font-bold">{{ $log->asset->name }}</td>
                                    <td class="text-right text-danger-600 font-mono">-{{ number_format($log->depreciation_amount, 2) }}</td>
                                    <td class="text-right font-black font-mono text-primary-600">{{ number_format($log->book_value, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            @elseif($activeTab === 'aging')
                <div class="report-table-wrapper custom-scrollbar mt-24">
                    <table class="report-inner-table min-w-[800px]">
                        <thead>
                            <tr>
                                <th>Asset Name</th>
                                <th>Purchase Date</th>
                                <th class="text-center">Age (Years)</th>
                                <th class="text-center">Remaining (Years)</th>
                                <th class="text-center">Useful Life</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reportData as $row)
                                <tr>
                                    <td class="font-bold">{{ $row['name'] }}</td>
                                    <td class="font-mono">{{ $row['purchase_date']->format('d/m/Y') }}</td>
                                    <td class="text-center font-black">{{ $row['age_years'] }}</td>
                                    <td class="text-center">
                                        <x-filament::badge :color="$row['remaining_life'] < 2 ? 'danger' : 'success'">
                                            {{ $row['remaining_life'] }}
                                        </x-filament::badge>
                                    </td>
                                    <td class="text-center opacity-70">{{ $row['useful_life'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            @elseif($activeTab === 'utilization')
                <div class="report-table-wrapper custom-scrollbar mt-24">
                    <table class="report-inner-table min-w-[800px]">
                        <thead>
                            <tr>
                                <th>Asset Name</th>
                                <th class="text-center">Total Stock</th>
                                <th class="text-center">Currently Assigned</th>
                                <th class="text-center">Workload %</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reportData as $row)
                                <tr>
                                    <td class="font-bold">{{ $row['name'] }}</td>
                                    <td class="text-center font-mono">{{ $row['total_qty'] }}</td>
                                    <td class="text-center font-mono font-bold text-primary-600">{{ $row['assigned_qty'] }}</td>
                                    <td class="text-center">
                                        <div class="flex items-center gap-2">
                                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 min-w-[100px]">
                                                <div class="bg-primary-600 h-2 rounded-full" style="width: {{ $row['utilization_rate'] }}%"></div>
                                            </div>
                                            <span class="text-[10px] font-black">{{ number_format($row['utilization_rate'], 1) }}%</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            @elseif($activeTab === 'movement')
                <div class="report-table-wrapper custom-scrollbar mt-24">
                    <table class="report-inner-table min-w-[1000px]">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Asset</th>
                                <th>Type</th>
                                <th class="text-center">Qty</th>
                                <th>Origin</th>
                                <th>Destination</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reportData as $move)
                                <tr>
                                    <td class="font-mono opacity-70">{{ $move->date->format('d/m/Y') }}</td>
                                    <td class="font-bold text-primary-600 leading-tight">
                                        {{ $move->asset->name }}
                                        <div class="text-[10px] text-gray-500 font-normal">REF: {{ $move->reference_no }}</div>
                                    </td>
                                    <td>
                                        <x-filament::badge :color="match($move->type) {
                                            'Stock In' => 'success',
                                            'Stock Out' => 'danger',
                                            'Transfer' => 'info',
                                            default => 'gray'
                                        }">{{ $move->type }}</x-filament::badge>
                                    </td>
                                    <td class="text-center font-black">{{ $move->quantity }}</td>
                                    <td class="text-[11px] font-medium">{{ $move->fromLocation->name ?? 'SYSTEM/PO' }}</td>
                                    <td class="text-[11px] font-medium">{{ $move->toLocation->name ?? 'RECIPIENT' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            @elseif($activeTab === 'damaged')
                <div class="report-table-wrapper custom-scrollbar mt-24">
                    <table class="report-inner-table min-w-[800px]">
                        <thead>
                            <tr>
                                <th>Asset Name</th>
                                <th>Status</th>
                                <th>Condition</th>
                                <th>Current Holding</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reportData as $asset)
                                <tr>
                                    <td class="font-bold leading-tight">{{ $asset->name }} <br> <span class="text-[10px] font-normal opacity-50">{{ $asset->barcode }}</span></td>
                                    <td><x-filament::badge color="danger">{{ $asset->status }}</x-filament::badge></td>
                                    <td><x-filament::badge color="warning">{{ $asset->condition }}</x-filament::badge></td>
                                    <td class="opacity-70">{{ $asset->location->name ?? 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="p-8 text-center text-gray-500 italic">No lost or damaged assets found in logs</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            @elseif($activeTab === 'location' || $activeTab === 'category')
                <div class="report-table-wrapper custom-scrollbar mt-24">
                    <table class="report-inner-table min-w-[600px]">
                        <thead>
                            <tr>
                                <th>Grouping Name</th>
                                <th class="text-center">Asset Count</th>
                                <th class="text-right">Financial Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reportData as $row)
                                <tr>
                                    <td class="font-bold">{{ $row['name'] }}</td>
                                    <td class="text-center font-black">{{ number_format($row['asset_count']) }}</td>
                                    <td class="text-right font-mono font-black text-primary-600">Rs. {{ number_format($row['total_value'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            @elseif($activeTab === 'procurement')
                <div class="grid grid-cols-1 md:grid-cols-4 gap-12 mb-20 mt-10">
                    <x-filament::section><p class="text-[10px] font-bold uppercase text-gray-500">PRs Issued</p><p class="text-2xl font-black">{{ $reportData['requests_count'] }}</p></x-filament::section>
                    <x-filament::section><p class="text-[10px] font-bold uppercase text-gray-500">POs Generated</p><p class="text-2xl font-black">{{ $reportData['orders_count'] }}</p></x-filament::section>
                    <x-filament::section><p class="text-[10px] font-bold uppercase text-gray-500">Value Requested</p><p class="text-2xl font-black text-primary-600">{{ number_format($reportData['total_requested'], 2) }}</p></x-filament::section>
                    <x-filament::section><p class="text-[10px] font-bold uppercase text-gray-500">Value Ordered</p><p class="text-2xl font-black text-success-600">{{ number_format($reportData['total_ordered'], 2) }}</p></x-filament::section>
                </div>

                <div class="report-table-wrapper custom-scrollbar mt-24">
                    <table class="report-inner-table min-w-[800px]">
                        <thead>
                            <tr>
                                <th>PO Number</th>
                                <th>Supplier</th>
                                <th>Date</th>
                                <th class="text-right">Amount</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reportData['recent_orders'] as $po)
                                <tr>
                                    <td class="font-black text-primary-600">{{ $po->po_number }}</td>
                                    <td class="font-bold">{{ $po->supplier->name }}</td>
                                    <td class="font-mono text-xs">{{ $po->po_date->format('d/m/Y') }}</td>
                                    <td class="text-right font-mono font-bold">{{ number_format($po->total_amount, 2) }}</td>
                                    <td class="text-center"><x-filament::badge>{{ $po->status }}</x-filament::badge></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            @elseif($activeTab === 'turnover')
                <div class="report-table-wrapper custom-scrollbar mt-24">
                    <table class="report-inner-table min-w-[800px]">
                        <thead>
                            <tr>
                                <th>Asset Name</th>
                                <th class="text-center">Units Out</th>
                                <th class="text-center">Available Stock</th>
                                <th class="text-center">Turnover Ratio</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reportData as $row)
                                <tr>
                                    <td class="font-bold">{{ $row['name'] }}</td>
                                    <td class="text-center font-mono">{{ number_format($row['outgoing']) }}</td>
                                    <td class="text-center font-mono">{{ number_format($row['current_stock']) }}</td>
                                    <td class="text-center">
                                        <span class="px-3 py-1 bg-primary-600/10 text-primary-600 rounded-full font-black font-mono">
                                            {{ number_format($row['turnover_ratio'], 2) }}x
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>

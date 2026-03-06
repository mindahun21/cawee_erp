<x-filament-panels::page>
    @if (! $this->excelImportEnabled)
        <x-filament::section>
            <x-slot name="heading">Excel Import</x-slot>
            <div class="text-sm text-danger-600">
                Excel import not enabled. Run composer require maatwebsite/excel
            </div>
        </x-filament::section>
    @else
        <x-filament::section>
            <x-slot name="heading">Support Files</x-slot>

            <div class="text-sm text-gray-600 mb-3">
                Download the template CSV first and use the exact fixed columns only. Do not create dynamic columns like "Women Amount Planned". Use <code>target_name</code>, <code>planned_value</code>, <code>actual_value</code>, and optional <code>report_time</code>.
            </div>

            <div class="flex flex-wrap gap-3 mb-4">
                <x-filament::button type="button" wire:click="downloadAcceptedColumnsCsv" color="gray" icon="heroicon-o-arrow-down-tray">
                    Download Template CSV (Row-Based v3)
                </x-filament::button>

                <x-filament::button type="button" wire:click="downloadSchemaMappingPdf" color="gray" icon="heroicon-o-document-text">
                    Download Guide PDF
                </x-filament::button>
            </div>
        </x-filament::section>

        <form wire:submit="import">
            <x-filament::section>
                <x-slot name="heading">Import Periodic Report</x-slot>

                {{ $this->form }}

                <x-slot name="footer">
                    <x-filament::button type="submit">
                        Import
                    </x-filament::button>
                </x-slot>
            </x-filament::section>
        </form>

        @if ($this->lastImportSummary !== [])
            <x-filament::section>
                <x-slot name="heading">Import Diagnostics</x-slot>

                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-xl border border-gray-200 dark:border-gray-800 p-4">
                        <div class="text-xs text-gray-500">Rows Processed</div>
                        <div class="text-2xl font-semibold">{{ number_format((int) ($this->lastImportSummary['rows_processed'] ?? 0)) }}</div>
                    </div>
                    <div class="rounded-xl border border-gray-200 dark:border-gray-800 p-4">
                        <div class="text-xs text-gray-500">Rows Rejected</div>
                        <div class="text-2xl font-semibold text-danger-600">{{ number_format(count($this->rejectedRows)) }}</div>
                    </div>
                    <div class="rounded-xl border border-gray-200 dark:border-gray-800 p-4">
                        <div class="text-xs text-gray-500">Reports Created</div>
                        <div class="text-2xl font-semibold">{{ number_format((int) ($this->lastImportSummary['reports_created'] ?? 0)) }}</div>
                    </div>
                    <div class="rounded-xl border border-gray-200 dark:border-gray-800 p-4">
                        <div class="text-xs text-gray-500">Reports Updated</div>
                        <div class="text-2xl font-semibold">{{ number_format((int) ($this->lastImportSummary['reports_updated'] ?? 0)) }}</div>
                    </div>
                </div>

                <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-6">
                    <div class="rounded-xl border border-gray-200 dark:border-gray-800 p-4">
                        <div class="text-xs text-gray-500">Targets Created</div>
                        <div class="text-2xl font-semibold">{{ number_format((int) ($this->lastImportSummary['targets_created'] ?? 0)) }}</div>
                    </div>
                    <div class="rounded-xl border border-gray-200 dark:border-gray-800 p-4">
                        <div class="text-xs text-gray-500">Targets Updated</div>
                        <div class="text-2xl font-semibold">{{ number_format((int) ($this->lastImportSummary['targets_updated'] ?? 0)) }}</div>
                    </div>
                    <div class="rounded-xl border border-gray-200 dark:border-gray-800 p-4">
                        <div class="text-xs text-gray-500">Targets Unchanged</div>
                        <div class="text-2xl font-semibold">{{ number_format((int) ($this->lastImportSummary['targets_unchanged'] ?? 0)) }}</div>
                    </div>
                    <div class="rounded-xl border border-gray-200 dark:border-gray-800 p-4">
                        <div class="text-xs text-gray-500">Reports Unchanged</div>
                        <div class="text-2xl font-semibold">{{ number_format((int) ($this->lastImportSummary['reports_unchanged'] ?? 0)) }}</div>
                    </div>
                    <div class="rounded-xl border border-gray-200 dark:border-gray-800 p-4">
                        <div class="text-xs text-gray-500">Rows Failed</div>
                        <div class="text-2xl font-semibold text-amber-600">{{ number_format((int) ($this->lastImportSummary['rows_failed'] ?? 0)) }}</div>
                    </div>
                    <div class="rounded-xl border border-gray-200 dark:border-gray-800 p-4">
                        <div class="text-xs text-gray-500">Duplicates In File</div>
                        <div class="text-2xl font-semibold">{{ number_format((int) ($this->lastImportSummary['duplicate_rows_in_file'] ?? (($this->lastImportSummary['duplicate_target_rows_in_file'] ?? 0) + ($this->lastImportSummary['duplicate_report_rows_in_file'] ?? 0)))) }}</div>
                    </div>
                </div>

                <div class="mt-6 grid gap-6 xl:grid-cols-2">
                    <div class="rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
                        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900/40 flex items-center justify-between">
                            <div class="text-sm font-semibold">Rejected Rows (Reason)</div>
                            <div class="flex gap-2">
                                @if ($this->rejectedRowsCsvPath)
                                    <x-filament::button type="button" wire:click="downloadRejectedRows" color="danger" size="sm" icon="heroicon-o-arrow-down-tray">
                                        Download Rejected CSV
                                    </x-filament::button>
                                @endif
                            </div>
                        </div>
                        <div style="max-height: 14rem; overflow-y: auto;">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50 dark:bg-gray-900/40 sticky top-0">
                                    <tr>
                                        <th class="px-3 py-2 text-left">Row</th>
                                        <th class="px-3 py-2 text-left">Project</th>
                                        <th class="px-3 py-2 text-left">Period</th>
                                        <th class="px-3 py-2 text-left">Reason</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($this->rejectedRows as $row)
                                        <tr class="border-t border-gray-100 dark:border-gray-800">
                                            <td class="px-3 py-2">{{ $row['row'] ?? '-' }}</td>
                                            <td class="px-3 py-2">{{ $row['project_code'] ?? '-' }}</td>
                                            <td class="px-3 py-2">{{ $row['period'] ?? '-' }}</td>
                                            <td class="px-3 py-2">{{ $row['reason'] ?? '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td class="px-3 py-3 text-gray-500" colspan="4">No rejected rows.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
                        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900/40 flex items-center justify-between">
                            <div class="text-sm font-semibold">Accepted Rows Preview</div>
                            <div>
                                @if ($this->debugReportPath)
                                    <x-filament::button type="button" wire:click="downloadDebugReport" color="gray" size="sm" icon="heroicon-o-document-arrow-down">
                                        Download Debug JSON
                                    </x-filament::button>
                                @endif
                            </div>
                        </div>
                        <div style="max-height: 14rem; overflow-y: auto;">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50 dark:bg-gray-900/40 sticky top-0">
                                    <tr>
                                        <th class="px-3 py-2 text-left">Row</th>
                                        <th class="px-3 py-2 text-left">Project</th>
                                        <th class="px-3 py-2 text-left">Period</th>
                                        <th class="px-3 py-2 text-left">Metrics Written</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($this->acceptedRowsPreview as $row)
                                        <tr class="border-t border-gray-100 dark:border-gray-800">
                                            <td class="px-3 py-2">{{ $row['row'] ?? '-' }}</td>
                                            <td class="px-3 py-2">{{ $row['project_code'] ?? '-' }}</td>
                                            <td class="px-3 py-2">{{ $row['period'] ?? '-' }}</td>
                                            <td class="px-3 py-2">{{ $row['metrics_written'] ?? '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td class="px-3 py-3 text-gray-500" colspan="4">No accepted rows preview.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </x-filament::section>
        @endif
    @endif
</x-filament-panels::page>

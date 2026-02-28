<x-filament-panels::page>
    @if (! $this->excelImportEnabled)
        <x-filament::section>
            <x-slot name="heading">Excel Import</x-slot>
            <div class="text-sm text-danger-600">
                Excel import not enabled. Run composer require maatwebsite/excel
            </div>
        </x-filament::section>
    @else
        <form wire:submit="import">
            <x-filament::section>
                <x-slot name="heading">Import Weekly Report</x-slot>

                <div class="text-sm text-gray-500 mb-4">
                    Accepted format: weekly wide sheet (Google-Form style) with project name/code, period, and plan/actual metric columns.
                    The importer auto-detects indicator columns, creates missing indicators/projects/periods, and writes a debug JSON report.
                </div>

                {{ $this->form }}

                <x-slot name="footer">
                    <x-filament::button type="submit">
                        Import
                    </x-filament::button>
                </x-slot>
            </x-filament::section>
        </form>
    @endif
</x-filament-panels::page>

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
                    Expected columns: <code>indicator_code, period_start, period_end, actual_value, scope_location, scope_project, gender, age, disability</code>
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

<?php

namespace App\Filament\Resources\Assets\DepreciationLogResource\Pages;

use App\Filament\Resources\Assets\DepreciationLogResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Carbon;

class ListDepreciationLogs extends ListRecords
{
    protected static string $resource = DepreciationLogResource::class;

    /**
     * The currently selected depreciation period.
     * Livewire public property — reactive across the component.
     */
    public string $depreciationPeriod = '';

    public function mount(): void
    {
        parent::mount();
        // Default to the current month on page load
        $this->depreciationPeriod = now()->format('Y-m');
    }

    protected function getHeaderActions(): array
    {
        return [
            // Read-only view
        ];
    }
}

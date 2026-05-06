<?php

namespace App\Filament\Resources\Maintenances\Pages;

use App\Filament\Resources\Maintenances\MaintenanceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMaintenances extends ListRecords
{
    protected static string $resource = MaintenanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Resources\Maintenances\Widgets\MaintenanceStatsOverview::class,
        ];
    }
}

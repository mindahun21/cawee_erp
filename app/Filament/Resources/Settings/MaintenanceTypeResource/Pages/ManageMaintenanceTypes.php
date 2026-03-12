<?php

namespace App\Filament\Resources\Settings\MaintenanceTypeResource\Pages;

use App\Filament\Resources\Settings\MaintenanceTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageMaintenanceTypes extends ManageRecords
{
    protected static string $resource = MaintenanceTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

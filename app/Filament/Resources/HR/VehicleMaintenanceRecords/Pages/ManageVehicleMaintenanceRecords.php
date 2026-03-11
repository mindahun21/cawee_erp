<?php

namespace App\Filament\Resources\HR\VehicleMaintenanceRecords\Pages;

use App\Filament\Resources\HR\VehicleMaintenanceRecords\VehicleMaintenanceRecordResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageVehicleMaintenanceRecords extends ManageRecords
{
    protected static string $resource = VehicleMaintenanceRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}


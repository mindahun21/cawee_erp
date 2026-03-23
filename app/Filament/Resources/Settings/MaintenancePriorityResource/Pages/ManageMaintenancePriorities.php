<?php

namespace App\Filament\Resources\Settings\MaintenancePriorityResource\Pages;

use App\Filament\Resources\Settings\MaintenancePriorityResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageMaintenancePriorities extends ManageRecords
{
    protected static string $resource = MaintenancePriorityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

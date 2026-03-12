<?php

namespace App\Filament\Resources\Procurement\Settings\Pages;

use App\Filament\Concerns\HasProcurementSettingsNavigation;
use App\Filament\Resources\Procurement\Settings\ProcurementUnitResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageProcurementUnits extends ManageRecords
{
    use HasProcurementSettingsNavigation;

    protected static string $resource = ProcurementUnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Add Unit'),
        ];
    }
}

<?php

namespace App\Filament\Resources\Procurement\Settings\Pages;

use App\Filament\Concerns\HasProcurementSettingsNavigation;
use App\Filament\Resources\Procurement\Settings\ProcurementMethodResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageProcurementMethods extends ManageRecords
{
    use HasProcurementSettingsNavigation;

    protected static string $resource = ProcurementMethodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

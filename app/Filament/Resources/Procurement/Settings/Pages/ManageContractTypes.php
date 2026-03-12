<?php

namespace App\Filament\Resources\Procurement\Settings\Pages;

use App\Filament\Concerns\HasProcurementSettingsNavigation;
use App\Filament\Resources\Procurement\Settings\ContractTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageContractTypes extends ManageRecords
{
    use HasProcurementSettingsNavigation;

    protected static string $resource = ContractTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Add Contract Type'),
        ];
    }
}

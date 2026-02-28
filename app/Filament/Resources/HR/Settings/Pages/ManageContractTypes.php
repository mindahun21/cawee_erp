<?php

namespace App\Filament\Resources\HR\Settings\Pages;

use App\Filament\Resources\HR\Settings\ContractTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageContractTypes extends ManageRecords
{
    protected static string $resource = ContractTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

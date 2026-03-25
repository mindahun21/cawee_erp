<?php

namespace App\Filament\Resources\Finance\Settings\Pages;

use App\Filament\Concerns\HasFinanceSettingsNavigation;
use App\Filament\Resources\Finance\Settings\TaxTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageTaxTypes extends ManageRecords
{
    use HasFinanceSettingsNavigation;

    protected static string $resource = TaxTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('New Tax Type'),
        ];
    }
}

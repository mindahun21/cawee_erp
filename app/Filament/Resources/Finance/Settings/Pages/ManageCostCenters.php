<?php

namespace App\Filament\Resources\Finance\Settings\Pages;

use App\Filament\Concerns\HasFinanceSettingsNavigation;
use App\Filament\Resources\Finance\Settings\CostCenterResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCostCenters extends ManageRecords
{
    use HasFinanceSettingsNavigation;

    protected static string $resource = CostCenterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('New Cost Center'),
        ];
    }
}

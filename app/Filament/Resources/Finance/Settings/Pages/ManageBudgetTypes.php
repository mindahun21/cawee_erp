<?php

namespace App\Filament\Resources\Finance\Settings\Pages;

use App\Filament\Concerns\HasFinanceSettingsNavigation;
use App\Filament\Resources\Finance\Settings\BudgetTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageBudgetTypes extends ManageRecords
{
    use HasFinanceSettingsNavigation;

    protected static string $resource = BudgetTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('New Budget Type'),
        ];
    }
}

<?php

namespace App\Filament\Resources\Finance\Settings\Pages;

use App\Filament\Concerns\HasFinanceSettingsNavigation;
use App\Filament\Resources\Finance\Settings\CashierResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCashiers extends ManageRecords
{
    use HasFinanceSettingsNavigation;

    protected static string $resource = CashierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('New Cashier'),
        ];
    }
}

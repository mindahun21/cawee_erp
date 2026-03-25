<?php

namespace App\Filament\Resources\Finance\Settings\Pages;

use App\Filament\Concerns\HasFinanceSettingsNavigation;
use App\Filament\Resources\Finance\Settings\AccountTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageAccountTypes extends ManageRecords
{
    use HasFinanceSettingsNavigation;

    protected static string $resource = AccountTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('New Account Type'),
        ];
    }
}

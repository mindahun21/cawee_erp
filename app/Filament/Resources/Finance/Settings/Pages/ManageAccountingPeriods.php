<?php

namespace App\Filament\Resources\Finance\Settings\Pages;

use App\Filament\Concerns\HasFinanceSettingsNavigation;
use App\Filament\Resources\Finance\Settings\AccountingPeriodResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageAccountingPeriods extends ManageRecords
{
    use HasFinanceSettingsNavigation;

    protected static string $resource = AccountingPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('New Period'),
        ];
    }
}

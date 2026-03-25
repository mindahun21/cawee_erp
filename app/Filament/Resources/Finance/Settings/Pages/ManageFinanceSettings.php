<?php

namespace App\Filament\Resources\Finance\Settings\Pages;

use App\Filament\Concerns\HasFinanceSettingsNavigation;
use App\Filament\Resources\Finance\Settings\FinanceSettingResource;
use Filament\Resources\Pages\ManageRecords;

class ManageFinanceSettings extends ManageRecords
{
    use HasFinanceSettingsNavigation;

    protected static string $resource = FinanceSettingResource::class;

    protected function getHeaderActions(): array
    {
        return []; // Settings are seeded; no create action
    }
}

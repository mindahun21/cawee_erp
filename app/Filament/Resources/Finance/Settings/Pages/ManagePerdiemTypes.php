<?php

namespace App\Filament\Resources\Finance\Settings\Pages;

use App\Filament\Concerns\HasFinanceSettingsNavigation;
use App\Filament\Resources\Finance\Settings\PerdiemTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePerdiemTypes extends ManageRecords
{
    use HasFinanceSettingsNavigation;

    protected static string $resource = PerdiemTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('New Per Diem Type'),
        ];
    }
}

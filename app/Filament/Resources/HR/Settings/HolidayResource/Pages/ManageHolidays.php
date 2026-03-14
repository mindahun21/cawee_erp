<?php

namespace App\Filament\Resources\HR\Settings\HolidayResource\Pages;

use App\Filament\Concerns\HasHrSettingsNavigation;
use App\Filament\Resources\HR\Settings\HolidayResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageHolidays extends ManageRecords
{
    use HasHrSettingsNavigation;
    protected static string $resource = HolidayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

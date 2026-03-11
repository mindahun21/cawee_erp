<?php

namespace App\Filament\Resources\HR\Settings\Pages;

use App\Filament\Concerns\HasHrSettingsNavigation;
use App\Filament\Resources\HR\Settings\HrSettingOptionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageHrSettingOptions extends ManageRecords
{
    use HasHrSettingsNavigation;

    protected static string $resource = HrSettingOptionResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}


<?php

namespace App\Filament\Resources\HR\Settings\Pages;

use App\Filament\Concerns\HasHrSettingsNavigation;
use App\Filament\Resources\HR\Settings\LandlordResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageLandlords extends ManageRecords
{
    use HasHrSettingsNavigation;

    protected static string $resource = LandlordResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}


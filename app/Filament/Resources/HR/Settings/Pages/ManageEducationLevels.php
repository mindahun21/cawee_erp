<?php

namespace App\Filament\Resources\HR\Settings\Pages;

use App\Filament\Concerns\HasHrSettingsNavigation;
use App\Filament\Resources\HR\Settings\EducationLevelResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageEducationLevels extends ManageRecords
{
    use HasHrSettingsNavigation;

    protected static string $resource = EducationLevelResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

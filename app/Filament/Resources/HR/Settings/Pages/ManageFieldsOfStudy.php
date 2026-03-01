<?php

namespace App\Filament\Resources\HR\Settings\Pages;

use App\Filament\Concerns\HasHrSettingsNavigation;
use App\Filament\Resources\HR\Settings\FieldOfStudyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageFieldsOfStudy extends ManageRecords
{
    use HasHrSettingsNavigation;

    protected static string $resource = FieldOfStudyResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

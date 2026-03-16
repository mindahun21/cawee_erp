<?php

namespace App\Filament\Resources\HR\Settings\Pages;

use App\Filament\Concerns\HasHrSettingsNavigation;
use App\Filament\Resources\HR\Settings\GradeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageGrades extends ManageRecords
{
    use HasHrSettingsNavigation;

    protected static string $resource = GradeResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

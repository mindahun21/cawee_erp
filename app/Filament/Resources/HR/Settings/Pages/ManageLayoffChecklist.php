<?php

namespace App\Filament\Resources\HR\Settings\Pages;

use App\Filament\Concerns\HasHrSettingsNavigation;
use App\Filament\Resources\HR\Settings\LayoffChecklistResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageLayoffChecklist extends ManageRecords
{
    use HasHrSettingsNavigation;

    protected static string $resource = LayoffChecklistResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

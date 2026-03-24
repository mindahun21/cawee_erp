<?php

declare(strict_types=1);

namespace App\Filament\Resources\BRT\HouseholdResource\Pages;

use App\Filament\Resources\BRT\HouseholdResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewHousehold extends ViewRecord
{
    protected static string $resource = HouseholdResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make(), DeleteAction::make()];
    }
}

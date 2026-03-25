<?php

declare(strict_types=1);

namespace App\Filament\Resources\BRT\HouseholdResource\Pages;

use App\Filament\Resources\BRT\HouseholdResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditHousehold extends EditRecord
{
    protected static string $resource = HouseholdResource::class;

    protected function getHeaderActions(): array
    {
        return [ViewAction::make(), DeleteAction::make()];
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Resources\ME\HouseholdResource\Pages;

use App\Filament\Resources\ME\HouseholdResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditHousehold extends EditRecord
{
    protected static string $resource = HouseholdResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}

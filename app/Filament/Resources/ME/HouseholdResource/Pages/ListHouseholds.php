<?php

declare(strict_types=1);

namespace App\Filament\Resources\ME\HouseholdResource\Pages;

use App\Filament\Resources\ME\HouseholdResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHouseholds extends ListRecords
{
    protected static string $resource = HouseholdResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

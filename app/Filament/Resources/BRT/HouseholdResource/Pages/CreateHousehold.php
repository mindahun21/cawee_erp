<?php

declare(strict_types=1);

namespace App\Filament\Resources\BRT\HouseholdResource\Pages;

use App\Filament\Resources\BRT\HouseholdResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHousehold extends CreateRecord
{
    protected static string $resource = HouseholdResource::class;
}

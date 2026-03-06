<?php

namespace App\Filament\Resources\ME\AlertsResource\Pages;

use App\Filament\Resources\ME\AlertsResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAlert extends CreateRecord
{
    protected \Filament\Support\Enums\Width | string | null $maxContentWidth = \Filament\Support\Enums\Width::Full;
    protected static string $resource = AlertsResource::class;
}

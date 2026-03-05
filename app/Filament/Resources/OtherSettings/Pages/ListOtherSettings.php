<?php

namespace App\Filament\Resources\OtherSettings\Pages;

use App\Filament\Resources\OtherSettings\OtherSettingsResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOtherSettings extends ListRecords
{
    protected static string $resource = OtherSettingsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

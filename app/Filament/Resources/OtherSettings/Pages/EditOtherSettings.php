<?php

namespace App\Filament\Resources\OtherSettings\Pages;

use App\Filament\Resources\OtherSettings\OtherSettingsResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOtherSettings extends EditRecord
{
    protected static string $resource = OtherSettingsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

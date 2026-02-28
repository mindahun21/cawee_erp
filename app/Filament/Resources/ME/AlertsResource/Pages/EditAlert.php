<?php

namespace App\Filament\Resources\ME\AlertsResource\Pages;

use App\Filament\Resources\ME\AlertsResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAlert extends EditRecord
{
    protected static string $resource = AlertsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

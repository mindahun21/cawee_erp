<?php

namespace App\Filament\Resources\Settings\ItemTypeResource\Pages;

use App\Filament\Resources\Settings\ItemTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageItemTypes extends ManageRecords
{
    protected static string $resource = ItemTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

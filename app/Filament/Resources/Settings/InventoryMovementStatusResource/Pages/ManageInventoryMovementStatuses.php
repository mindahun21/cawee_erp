<?php

namespace App\Filament\Resources\Settings\InventoryMovementStatusResource\Pages;

use App\Filament\Resources\Settings\InventoryMovementStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageInventoryMovementStatuses extends ManageRecords
{
    protected static string $resource = InventoryMovementStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

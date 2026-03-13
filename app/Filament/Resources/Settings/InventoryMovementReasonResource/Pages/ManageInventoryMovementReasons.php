<?php

namespace App\Filament\Resources\Settings\InventoryMovementReasonResource\Pages;

use App\Filament\Resources\Settings\InventoryMovementReasonResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageInventoryMovementReasons extends ManageRecords
{
    protected static string $resource = InventoryMovementReasonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

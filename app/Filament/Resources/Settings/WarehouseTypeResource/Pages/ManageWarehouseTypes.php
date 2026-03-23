<?php

namespace App\Filament\Resources\Settings\WarehouseTypeResource\Pages;

use App\Filament\Resources\Settings\WarehouseTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageWarehouseTypes extends ManageRecords
{
    protected static string $resource = WarehouseTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

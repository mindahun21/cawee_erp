<?php

namespace App\Filament\Resources\Settings\AssetManufacturerResource\Pages;

use App\Filament\Resources\Settings\AssetManufacturerResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageAssetManufacturers extends ManageRecords
{
    protected static string $resource = AssetManufacturerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\Settings\AssetTypeResource\Pages;

use App\Filament\Resources\Settings\AssetTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageAssetTypes extends ManageRecords
{
    protected static string $resource = AssetTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

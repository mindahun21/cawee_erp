<?php

namespace App\Filament\Resources\Settings\AssetModelResource\Pages;

use App\Filament\Resources\Settings\AssetModelResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageAssetModels extends ManageRecords
{
    protected static string $resource = AssetModelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

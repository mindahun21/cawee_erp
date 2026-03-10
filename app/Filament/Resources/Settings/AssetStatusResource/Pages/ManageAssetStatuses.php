<?php

namespace App\Filament\Resources\Settings\AssetStatusResource\Pages;

use App\Filament\Resources\Settings\AssetStatusResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageAssetStatuses extends ManageRecords
{
    protected static string $resource = AssetStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

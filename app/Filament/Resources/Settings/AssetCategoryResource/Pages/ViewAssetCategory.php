<?php

namespace App\Filament\Resources\Settings\AssetCategoryResource\Pages;

use App\Filament\Resources\Settings\AssetCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAssetCategory extends ViewRecord
{
    protected static string $resource = AssetCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}

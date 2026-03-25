<?php

namespace App\Filament\Resources\Settings\AssetCategoryResource\Pages;

use App\Filament\Resources\Settings\AssetCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAssetCategory extends CreateRecord
{
    protected static string $resource = AssetCategoryResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

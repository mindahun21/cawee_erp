<?php

namespace App\Filament\Resources\Settings\AssetConditionResource\Pages;

use App\Filament\Resources\Settings\AssetConditionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageAssetConditions extends ManageRecords
{
    protected static string $resource = AssetConditionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

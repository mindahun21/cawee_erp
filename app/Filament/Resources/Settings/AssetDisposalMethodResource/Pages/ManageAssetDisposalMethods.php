<?php

namespace App\Filament\Resources\Settings\AssetDisposalMethodResource\Pages;

use App\Filament\Resources\Settings\AssetDisposalMethodResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageAssetDisposalMethods extends ManageRecords
{
    protected static string $resource = AssetDisposalMethodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\Settings\AcquisitionTypeResource\Pages;

use App\Filament\Resources\Settings\AcquisitionTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageAcquisitionTypes extends ManageRecords
{
    protected static string $resource = AcquisitionTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

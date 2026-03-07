<?php

namespace App\Filament\Resources\Donors\DonorCategoryResource\Pages;

use App\Filament\Resources\Donors\DonorCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewDonorCategory extends ViewRecord
{
    protected static string $resource = DonorCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}

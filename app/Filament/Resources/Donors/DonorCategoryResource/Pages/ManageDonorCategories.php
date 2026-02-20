<?php

namespace App\Filament\Resources\Donors\DonorCategoryResource\Pages;

use App\Filament\Resources\Donors\DonorCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageDonorCategories extends ManageRecords
{
    protected static string $resource = DonorCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Clusters\Settings\Resources\Procurement\Settings\ProcurementCategories\Pages;

use App\Filament\Clusters\Settings\Resources\Procurement\Settings\ProcurementCategories\ProcurementCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageProcurementCategories extends ManageRecords
{
    protected static string $resource = ProcurementCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

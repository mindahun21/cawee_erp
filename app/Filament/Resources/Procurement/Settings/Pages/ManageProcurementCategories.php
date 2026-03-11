<?php

namespace App\Filament\Resources\Procurement\Settings\Pages;

use App\Filament\Concerns\HasProcurementSettingsNavigation;
use App\Filament\Resources\Procurement\Settings\ProcurementCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageProcurementCategories extends ManageRecords
{
    use HasProcurementSettingsNavigation;

    protected static string $resource = ProcurementCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

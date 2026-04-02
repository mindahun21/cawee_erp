<?php

namespace App\Filament\Resources\Finance\Settings\Pages;

use App\Filament\Resources\Finance\Settings\FinancialStatementCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageFinancialStatementCategories extends ManageRecords
{
    protected static string $resource = FinancialStatementCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('New Category'),
        ];
    }
}

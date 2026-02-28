<?php

namespace App\Filament\Resources\ME\DisaggregationCategoryResource\Pages;

use App\Filament\Resources\ME\DisaggregationCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDisaggregationCategory extends EditRecord
{
    protected static string $resource = DisaggregationCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

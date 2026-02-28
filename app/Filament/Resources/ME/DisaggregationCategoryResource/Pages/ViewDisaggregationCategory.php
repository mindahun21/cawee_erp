<?php

namespace App\Filament\Resources\ME\DisaggregationCategoryResource\Pages;

use App\Filament\Resources\ME\DisaggregationCategoryResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewDisaggregationCategory extends ViewRecord
{
    protected static string $resource = DisaggregationCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}

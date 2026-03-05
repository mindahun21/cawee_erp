<?php

namespace App\Filament\Resources\ME\DisaggregationCategoryResource\Pages;

use App\Filament\Resources\ME\DisaggregationCategoryResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewDisaggregationCategory extends ViewRecord
{
    protected \Filament\Support\Enums\Width | string | null $maxContentWidth = \Filament\Support\Enums\Width::Full;
    protected static string $resource = DisaggregationCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}

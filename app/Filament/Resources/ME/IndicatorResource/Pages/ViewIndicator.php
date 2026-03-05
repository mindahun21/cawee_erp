<?php

namespace App\Filament\Resources\ME\IndicatorResource\Pages;

use App\Filament\Resources\ME\IndicatorResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewIndicator extends ViewRecord
{
    protected \Filament\Support\Enums\Width | string | null $maxContentWidth = \Filament\Support\Enums\Width::Full;
    protected static string $resource = IndicatorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}

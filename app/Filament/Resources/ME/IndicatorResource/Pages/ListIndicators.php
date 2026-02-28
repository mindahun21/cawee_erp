<?php

namespace App\Filament\Resources\ME\IndicatorResource\Pages;

use App\Filament\Resources\ME\IndicatorResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListIndicators extends ListRecords
{
    protected static string $resource = IndicatorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

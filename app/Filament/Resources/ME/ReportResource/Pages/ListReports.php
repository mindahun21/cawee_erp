<?php

namespace App\Filament\Resources\ME\ReportResource\Pages;

use App\Filament\Resources\ME\ReportResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListReports extends ListRecords
{
    protected \Filament\Support\Enums\Width | string | null $maxContentWidth = \Filament\Support\Enums\Width::Full;
    protected static string $resource = ReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\Donors\Pages;

use App\Filament\Resources\Donors\DonorResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageDonors extends ManageRecords
{
    protected static string $resource = DonorResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Resources\Donors\DonorResource\Widgets\DonorStatsWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

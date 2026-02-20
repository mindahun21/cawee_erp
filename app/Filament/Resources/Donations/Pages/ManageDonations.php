<?php

namespace App\Filament\Resources\Donations\Pages;

use App\Filament\Resources\Donations\DonationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageDonations extends ManageRecords
{
    protected static string $resource = DonationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Resources\Donations\Widgets\DonationTrendsChart::class,
            \App\Filament\Resources\Donations\Widgets\DonationTypeChart::class,
            \App\Filament\Resources\Donations\Widgets\TopDonorsWidget::class,
        ];
    }
}

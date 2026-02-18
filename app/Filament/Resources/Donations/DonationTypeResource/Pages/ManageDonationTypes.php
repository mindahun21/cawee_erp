<?php

namespace App\Filament\Resources\Donations\DonationTypeResource\Pages;

use App\Filament\Resources\Donations\DonationTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageDonationTypes extends ManageRecords
{
    protected static string $resource = DonationTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

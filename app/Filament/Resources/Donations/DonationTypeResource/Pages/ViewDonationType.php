<?php

namespace App\Filament\Resources\Donations\DonationTypeResource\Pages;

use App\Filament\Resources\Donations\DonationTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewDonationType extends ViewRecord
{
    protected static string $resource = DonationTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}

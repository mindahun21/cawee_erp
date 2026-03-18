<?php

namespace App\Filament\Resources\VehicleManagement\OfficeRentAgreements\Pages;

use App\Filament\Resources\VehicleManagement\OfficeRentAgreements\OfficeRentAgreementResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageOfficeRentAgreements extends ManageRecords
{
    protected static string $resource = OfficeRentAgreementResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

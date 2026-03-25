<?php

namespace App\Filament\Resources\VehicleManagement\UtilityPayments\Pages;

use App\Filament\Resources\VehicleManagement\UtilityPayments\UtilityPaymentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageUtilityPayments extends ManageRecords
{
    protected static string $resource = UtilityPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

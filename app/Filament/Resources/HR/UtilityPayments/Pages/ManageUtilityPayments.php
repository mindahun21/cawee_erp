<?php

namespace App\Filament\Resources\HR\UtilityPayments\Pages;

use App\Filament\Resources\HR\UtilityPayments\UtilityPaymentResource;
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


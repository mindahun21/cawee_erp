<?php

namespace App\Filament\Resources\Settings\PaymentTermResource\Pages;

use App\Filament\Resources\Settings\PaymentTermResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManagePaymentTerms extends ManageRecords
{
    protected static string $resource = PaymentTermResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

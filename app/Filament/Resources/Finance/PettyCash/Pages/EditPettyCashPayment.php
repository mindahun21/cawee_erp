<?php

namespace App\Filament\Resources\Finance\PettyCash\Pages;

use App\Filament\Resources\Finance\PettyCash\PettyCashPaymentResource;
use Filament\Resources\Pages\EditRecord;

class EditPettyCashPayment extends EditRecord
{
    protected static string $resource = PettyCashPaymentResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}

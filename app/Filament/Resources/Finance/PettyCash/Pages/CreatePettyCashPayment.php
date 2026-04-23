<?php

namespace App\Filament\Resources\Finance\PettyCash\Pages;

use App\Filament\Resources\Finance\PettyCash\PettyCashPaymentResource;
use App\Services\Finance\PettyCashService;
use Filament\Resources\Pages\CreateRecord;

class CreatePettyCashPayment extends CreateRecord
{
    protected static string $resource = PettyCashPaymentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $service = app(PettyCashService::class);
        $data['payment_number'] = $service->generatePaymentNumber();
        $data['prepared_by']    = auth()->id();
        $data['status']         = 'pending';
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}

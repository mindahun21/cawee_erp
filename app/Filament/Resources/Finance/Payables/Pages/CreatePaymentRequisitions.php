<?php

namespace App\Filament\Resources\Finance\Payables\Pages;

use App\Filament\Resources\Finance\Payables\PaymentRequisitionResource;
use App\Services\Finance\PaymentRequisitionService;
use Filament\Resources\Pages\CreateRecord;

class CreatePaymentRequisitions extends CreateRecord
{
    protected static string $resource = PaymentRequisitionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['pr_number']   = app(PaymentRequisitionService::class)->generatePrNumber(now()->year);
        $data['prepared_by'] = auth()->id();
        $data['status']      = 'draft';
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}

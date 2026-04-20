<?php

namespace App\Filament\Resources\Finance\Receivables\Pages;

use App\Filament\Resources\Finance\Receivables\IncomeRegisterResource;
use App\Services\Finance\PaymentRequisitionService;
use Filament\Resources\Pages\CreateRecord;

class CreateIncomeRegisters extends CreateRecord
{
    protected static string $resource = IncomeRegisterResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['reference']   = app(PaymentRequisitionService::class)->generateIrReference(now()->year);
        $data['prepared_by'] = auth()->id();
        $data['status']      = 'draft';

        // Auto-compute ETB equivalent
        if (! empty($data['amount']) && ! empty($data['exchange_rate_to_base'])) {
            $data['amount_in_base'] = round(
                (float) $data['amount'] * (float) $data['exchange_rate_to_base'],
                2
            );
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}

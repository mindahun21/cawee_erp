<?php

namespace App\Filament\Resources\Finance\Cash\Pages;

use App\Filament\Resources\Finance\Cash\CashReceiptVoucherResource;
use App\Services\Finance\VoucherService;
use Filament\Resources\Pages\CreateRecord;

class CreateCashReceiptVoucher extends CreateRecord
{
    protected static string $resource = CashReceiptVoucherResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $service = app(VoucherService::class);
        $data['crv_number'] = $service->generateCrvReference();
        $data['prepared_by'] = auth()->id();
        $data['status'] = 'draft';

        // Ensure amount_in_base is computed
        $data['amount_in_base'] = round(
            (float) ($data['amount'] ?? 0) * (float) ($data['exchange_rate_to_base'] ?? 1),
            2
        );

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}

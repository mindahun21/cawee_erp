<?php

namespace App\Filament\Resources\Finance\Cash\Pages;

use App\Filament\Resources\Finance\Cash\PaymentVoucherResource;
use App\Services\Finance\VoucherService;
use Filament\Resources\Pages\CreateRecord;

class CreatePaymentVoucher extends CreateRecord
{
    protected static string $resource = PaymentVoucherResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $service = app(VoucherService::class);
        $data['pv_number']   = $service->generatePvReference();
        $data['prepared_by'] = auth()->id();
        $data['status']      = 'draft';

        // Final tax recompute as safety net
        $gross   = (float) ($data['gross_amount'] ?? 0);
        $whtRate = (float) ($data['withholding_tax_rate'] ?? 0);
        $vatType = $data['vat_type'] ?? 'none';
        $vatRate = (float) ($data['vat_rate'] ?? 0);

        $data['withholding_tax_amount'] = round($gross * $whtRate, 2);
        $data['vat_amount']  = in_array($vatType, ['collected', 'payable'])
            ? round($gross * $vatRate, 2) : 0;
        $data['net_amount']  = round($gross - $data['withholding_tax_amount'], 2);

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}

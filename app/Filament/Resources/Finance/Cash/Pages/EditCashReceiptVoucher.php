<?php

namespace App\Filament\Resources\Finance\Cash\Pages;

use App\Filament\Resources\Finance\Cash\CashReceiptVoucherResource;
use Filament\Resources\Pages\EditRecord;

class EditCashReceiptVoucher extends EditRecord
{
    protected static string $resource = CashReceiptVoucherResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}

<?php

namespace App\Filament\Resources\Finance\Cash\Pages;

use App\Filament\Resources\Finance\Cash\PaymentVoucherResource;
use Filament\Resources\Pages\ListRecords;

class ListPaymentVouchers extends ListRecords
{
    protected static string $resource = PaymentVoucherResource::class;
    protected function getHeaderActions(): array { return [\Filament\Actions\CreateAction::make()]; }
}

<?php

namespace App\Filament\Resources\Finance\Cash\Pages;

use App\Filament\Resources\Finance\Cash\CashReceiptVoucherResource;
use App\Services\Finance\VoucherService;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ViewRecord;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;

class ListCashReceiptVouchers extends ListRecords
{
    protected static string $resource = CashReceiptVoucherResource::class;
    protected function getHeaderActions(): array { return [\Filament\Actions\CreateAction::make()]; }
}

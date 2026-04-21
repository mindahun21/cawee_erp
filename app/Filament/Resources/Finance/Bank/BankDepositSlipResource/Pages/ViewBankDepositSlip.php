<?php

namespace App\Filament\Resources\Finance\Bank\BankDepositSlipResource\Pages;

use App\Filament\Resources\Finance\Bank\BankDepositSlipResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewBankDepositSlip extends ViewRecord
{
    protected static string $resource = BankDepositSlipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}

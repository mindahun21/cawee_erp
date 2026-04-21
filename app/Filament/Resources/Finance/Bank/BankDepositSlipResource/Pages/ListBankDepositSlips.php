<?php

namespace App\Filament\Resources\Finance\Bank\BankDepositSlipResource\Pages;

use App\Filament\Resources\Finance\Bank\BankDepositSlipResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBankDepositSlips extends ListRecords
{
    protected static string $resource = BankDepositSlipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

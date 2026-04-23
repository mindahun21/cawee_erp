<?php

namespace App\Filament\Resources\Finance\Bank\BankAdviceResource\Pages;

use App\Filament\Resources\Finance\Bank\BankAdviceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBankAdvices extends ListRecords
{
    protected static string $resource = BankAdviceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

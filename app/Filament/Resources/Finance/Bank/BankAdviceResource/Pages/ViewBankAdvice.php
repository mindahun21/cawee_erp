<?php

namespace App\Filament\Resources\Finance\Bank\BankAdviceResource\Pages;

use App\Filament\Resources\Finance\Bank\BankAdviceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewBankAdvice extends ViewRecord
{
    protected static string $resource = BankAdviceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\Finance\Bank\Pages;

use App\Filament\Resources\Finance\Bank\FundTransferResource;
use Filament\Resources\Pages\EditRecord;

class EditFundTransfer extends EditRecord
{
    protected static string $resource = FundTransferResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}

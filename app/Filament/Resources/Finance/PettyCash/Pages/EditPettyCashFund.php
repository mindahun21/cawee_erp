<?php

namespace App\Filament\Resources\Finance\PettyCash\Pages;

use App\Filament\Resources\Finance\PettyCash\PettyCashFundResource;
use Filament\Resources\Pages\EditRecord;

class EditPettyCashFund extends EditRecord
{
    protected static string $resource = PettyCashFundResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}

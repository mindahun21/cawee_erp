<?php

namespace App\Filament\Resources\Finance\PettyCash\Pages;

use App\Filament\Resources\Finance\PettyCash\PettyCashReplenishmentResource;
use Filament\Resources\Pages\EditRecord;

class EditPettyCashReplenishment extends EditRecord
{
    protected static string $resource = PettyCashReplenishmentResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}

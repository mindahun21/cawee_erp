<?php

namespace App\Filament\Resources\Finance\PettyCash\Pages;

use App\Filament\Resources\Finance\PettyCash\PettyCashFundResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePettyCashFund extends CreateRecord
{
    protected static string $resource = PettyCashFundResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['current_balance'] = $data['opening_balance'] ?? 0;
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}

<?php

namespace App\Filament\Resources\Finance\Bank\Pages;

use App\Filament\Resources\Finance\Bank\BankAccountResource;
use Filament\Resources\Pages\EditRecord;

class EditBankAccount extends EditRecord
{
    protected static string $resource = BankAccountResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $newOpeningBalance = (float) ($data['opening_balance'] ?? 0);
        $oldOpeningBalance = (float) ($this->record->opening_balance ?? 0);
        $currentBalance = (float) ($this->record->current_balance ?? 0);

        // Keep current balance aligned with opening balance only when it has
        // not diverged from the previous opening value (i.e. no movements yet).
        if ($this->record->current_balance === null || abs($currentBalance - $oldOpeningBalance) < 0.00001) {
            $data['current_balance'] = $newOpeningBalance;
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}

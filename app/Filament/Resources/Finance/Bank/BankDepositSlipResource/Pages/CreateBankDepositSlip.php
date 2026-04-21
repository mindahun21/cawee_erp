<?php

namespace App\Filament\Resources\Finance\Bank\BankDepositSlipResource\Pages;

use App\Filament\Resources\Finance\Bank\BankDepositSlipResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBankDepositSlip extends CreateRecord
{
    protected static string $resource = BankDepositSlipResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['slip_number'])) {
            $data['slip_number'] = 'BDS-' . now()->format('Ymd-His');
        }

        $data['prepared_by'] ??= auth()->id();

        return $data;
    }
}

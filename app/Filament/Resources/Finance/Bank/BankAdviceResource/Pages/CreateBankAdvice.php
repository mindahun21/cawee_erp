<?php

namespace App\Filament\Resources\Finance\Bank\BankAdviceResource\Pages;

use App\Filament\Resources\Finance\Bank\BankAdviceResource;
use App\Models\Finance\BankAdvice;
use Filament\Resources\Pages\CreateRecord;

class CreateBankAdvice extends CreateRecord
{
    protected static string $resource = BankAdviceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['reference_number'])) {
            $data['reference_number'] = 'BA-' . now()->format('Ymd-His');
        }

        return $data;
    }
}

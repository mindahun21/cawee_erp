<?php

namespace App\Filament\Resources\Finance\Bank\Pages;

use App\Filament\Resources\Finance\Bank\FundTransferResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFundTransfer extends CreateRecord
{
    protected static string $resource = FundTransferResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Guard 1: same-account transfer is invalid
        if (($data['from_bank_account_id'] ?? null) === ($data['to_bank_account_id'] ?? null)) {
            \Filament\Notifications\Notification::make()
                ->danger()
                ->title('Invalid Transfer')
                ->body('Source and destination accounts must be different.')
                ->send();
            $this->halt();
        }

        // Guard 2: insufficient funds
        $fromAccount = \App\Models\Finance\BankAccount::find($data['from_bank_account_id'] ?? null);
        $amount      = (float) ($data['amount'] ?? 0);
        if ($fromAccount && $amount > (float) $fromAccount->current_balance) {
            \Filament\Notifications\Notification::make()
                ->danger()
                ->title('Insufficient Funds')
                ->body(
                    "Account '{$fromAccount->account_name}' only has "
                    . number_format((float)$fromAccount->current_balance, 2)
                    . " {$fromAccount->currency?->code}. Cannot transfer {$amount}."
                )
                ->send();
            $this->halt();
        }

        $year   = now()->year;
        $prefix = \App\Models\Finance\FinanceSetting::get('ft_number_prefix', 'FT');
        $like   = "{$prefix}-{$year}-%";
        $lastRef= \App\Models\Finance\FundTransfer::withTrashed()
            ->where('transfer_number', 'like', $like)
            ->orderByRaw('LENGTH(transfer_number) DESC')
            ->orderBy('transfer_number', 'desc')
            ->value('transfer_number');
        $seq    = $lastRef ? ((int) last(explode('-', $lastRef))) + 1 : 1;

        $data['transfer_number'] = sprintf('%s-%d-%04d', $prefix, $year, $seq);
        $data['prepared_by']     = auth()->id();
        $data['status']          = 'draft';
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}

<?php

namespace App\Filament\Resources\Donations\Pages;

use App\Filament\Resources\Donations\DonationResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewDonation extends ViewRecord
{
    protected static string $resource = DonationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->after(function (\Filament\Resources\Pages\ViewRecord $livewire) {
                    $livewire->refreshFormData([
                        'is_tax_deductible',
                        'is_gift_aid_eligible',
                        'exchange_rate',
                        'base_amount',
                        'in_kind_description',
                        'notes',
                        'amount'
                    ]);
                    // Alternatively, we can just fill form or reload
                    $livewire->fillForm();
                }),
            DeleteAction::make(),
        ];
    }
}

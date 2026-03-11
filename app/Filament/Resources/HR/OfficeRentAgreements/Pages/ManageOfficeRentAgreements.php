<?php

namespace App\Filament\Resources\HR\OfficeRentAgreements\Pages;

use App\Filament\Resources\HR\OfficeRentAgreements\OfficeRentAgreementResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageOfficeRentAgreements extends ManageRecords
{
    protected static string $resource = OfficeRentAgreementResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}


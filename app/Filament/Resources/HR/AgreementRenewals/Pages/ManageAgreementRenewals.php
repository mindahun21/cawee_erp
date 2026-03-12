<?php

namespace App\Filament\Resources\HR\AgreementRenewals\Pages;

use App\Filament\Resources\HR\AgreementRenewals\AgreementRenewalResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageAgreementRenewals extends ManageRecords
{
    protected static string $resource = AgreementRenewalResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}


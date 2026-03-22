<?php

declare(strict_types=1);

namespace App\Filament\Resources\ME\BeneficiaryResource\Pages;

use App\Filament\Resources\ME\BeneficiaryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBeneficiaries extends ListRecords
{
    protected static string $resource = BeneficiaryResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

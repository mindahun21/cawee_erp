<?php

namespace App\Filament\Resources\Finance\ChartOfAccounts\Pages;

use App\Filament\Resources\Finance\ChartOfAccounts\ChartOfAccountResource;
use Filament\Resources\Pages\CreateRecord;

class CreateChartOfAccount extends CreateRecord
{
    protected static string $resource = ChartOfAccountResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

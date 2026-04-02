<?php

namespace App\Filament\Resources\Finance\Journals\Pages;

use App\Filament\Resources\Finance\Journals\GeneralLedgerResource;
use Filament\Resources\Pages\ListRecords;

class ListGeneralLedgers extends ListRecords
{
    protected static string $resource = GeneralLedgerResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}

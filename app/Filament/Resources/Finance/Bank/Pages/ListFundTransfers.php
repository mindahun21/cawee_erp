<?php

namespace App\Filament\Resources\Finance\Bank\Pages;

use App\Filament\Resources\Finance\Bank\FundTransferResource;
use Filament\Resources\Pages\ListRecords;

class ListFundTransfers extends ListRecords
{
    protected static string $resource = FundTransferResource::class;
    protected function getHeaderActions(): array { return [\Filament\Actions\CreateAction::make()]; }
}

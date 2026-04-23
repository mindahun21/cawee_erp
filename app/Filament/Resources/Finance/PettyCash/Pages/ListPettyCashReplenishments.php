<?php

namespace App\Filament\Resources\Finance\PettyCash\Pages;

use App\Filament\Resources\Finance\PettyCash\PettyCashReplenishmentResource;
use Filament\Resources\Pages\ListRecords;

class ListPettyCashReplenishments extends ListRecords
{
    protected static string $resource = PettyCashReplenishmentResource::class;
    protected function getHeaderActions(): array { return [\Filament\Actions\CreateAction::make()]; }
}

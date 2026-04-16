<?php

namespace App\Filament\Resources\Finance\PettyCash\Pages;

use App\Filament\Resources\Finance\PettyCash\PettyCashPaymentResource;
use App\Services\Finance\PettyCashService;
use Filament\Resources\Pages\ListRecords;

class ListPettyCashPayments extends ListRecords
{
    protected static string $resource = PettyCashPaymentResource::class;
    protected function getHeaderActions(): array { return [\Filament\Actions\CreateAction::make()]; }
}

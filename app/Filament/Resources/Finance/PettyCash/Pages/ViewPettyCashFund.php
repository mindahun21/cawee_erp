<?php

namespace App\Filament\Resources\Finance\PettyCash\Pages;

use App\Filament\Resources\Finance\PettyCash\PettyCashFundResource;
use Filament\Resources\Pages\ViewRecord;

class ViewPettyCashFund extends ViewRecord
{
    protected static string $resource = PettyCashFundResource::class;
    protected function getHeaderActions(): array
    {
        return [\Filament\Actions\EditAction::make()];
    }
}

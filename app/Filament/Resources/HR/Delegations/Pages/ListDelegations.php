<?php

namespace App\Filament\Resources\HR\Delegations\Pages;

use App\Filament\Resources\HR\Delegations\DelegationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDelegations extends ListRecords
{
    protected static string $resource = DelegationResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

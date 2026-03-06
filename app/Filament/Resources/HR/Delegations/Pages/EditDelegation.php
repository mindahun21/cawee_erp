<?php

namespace App\Filament\Resources\HR\Delegations\Pages;

use App\Filament\Resources\HR\Delegations\DelegationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDelegation extends EditRecord
{
    protected static string $resource = DelegationResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}

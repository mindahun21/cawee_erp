<?php

namespace App\Filament\Resources\Donations\PledgeResource\Pages;

use App\Filament\Resources\Donations\PledgeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPledges extends ListRecords
{
    protected static string $resource = PledgeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

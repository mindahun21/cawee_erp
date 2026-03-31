<?php

namespace App\Filament\Resources\Donations\PledgeResource\Pages;

use App\Filament\Resources\Donations\PledgeResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePledge extends CreateRecord
{
    protected static string $resource = PledgeResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

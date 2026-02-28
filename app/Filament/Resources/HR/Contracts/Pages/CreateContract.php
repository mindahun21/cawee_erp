<?php

namespace App\Filament\Resources\HR\Contracts\Pages;

use App\Filament\Resources\HR\Contracts\ContractResource;
use Filament\Resources\Pages\CreateRecord;

class CreateContract extends CreateRecord
{
    protected static string $resource = ContractResource::class;
}

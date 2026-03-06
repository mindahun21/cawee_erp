<?php

namespace App\Filament\Resources\Procurement\Requisitions\Pages;

use App\Filament\Resources\Procurement\Requisitions\RequisitionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRequisition extends CreateRecord
{
    protected static string $resource = RequisitionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

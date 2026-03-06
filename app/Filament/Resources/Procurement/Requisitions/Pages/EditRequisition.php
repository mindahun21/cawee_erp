<?php

namespace App\Filament\Resources\Procurement\Requisitions\Pages;

use App\Filament\Resources\Procurement\Requisitions\RequisitionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRequisition extends EditRecord
{
    protected static string $resource = RequisitionResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

<?php

namespace App\Filament\Resources\Procurement\Requisitions\Pages;

use App\Filament\Resources\Procurement\Requisitions\RequisitionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRequisitions extends ListRecords
{
    protected static string $resource = RequisitionResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('New Requisition')];
    }
}

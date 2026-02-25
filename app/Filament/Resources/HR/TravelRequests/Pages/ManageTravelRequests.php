<?php

namespace App\Filament\Resources\HR\TravelRequests\Pages;

use App\Filament\Resources\HR\TravelRequests\TravelRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageTravelRequests extends ManageRecords
{
    protected static string $resource = TravelRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\Finance\PettyCash\Pages;

use App\Filament\Resources\Finance\PettyCash\PettyCashReplenishmentResource;
use App\Services\Finance\PettyCashService;
use Filament\Resources\Pages\CreateRecord;

class CreatePettyCashReplenishment extends CreateRecord
{
    protected static string $resource = PettyCashReplenishmentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $service = app(PettyCashService::class);
        $data['replenishment_number'] = $service->generateReplenishmentNumber();
        $data['requested_by']         = auth()->id();
        $data['status']               = 'draft';
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}

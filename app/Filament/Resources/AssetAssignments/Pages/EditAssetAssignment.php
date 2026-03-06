<?php

namespace App\Filament\Resources\AssetAssignments\Pages;

use App\Filament\Resources\AssetAssignments\AssetAssignmentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAssetAssignment extends EditRecord
{
    protected static string $resource = AssetAssignmentResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\AssetAssignments\Pages;

use App\Filament\Resources\AssetAssignments\AssetAssignmentResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAssetAssignment extends ViewRecord
{
    protected static string $resource = AssetAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}

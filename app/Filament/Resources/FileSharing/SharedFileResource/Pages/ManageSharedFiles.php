<?php

namespace App\Filament\Resources\FileSharing\SharedFileResource\Pages;

use App\Filament\Resources\FileSharing\SharedFileResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageSharedFiles extends ManageRecords
{
    protected static string $resource = SharedFileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

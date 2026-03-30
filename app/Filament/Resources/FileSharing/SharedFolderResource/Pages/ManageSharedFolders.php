<?php

namespace App\Filament\Resources\FileSharing\SharedFolderResource\Pages;

use App\Filament\Resources\FileSharing\SharedFolderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageSharedFolders extends ManageRecords
{
    protected static string $resource = SharedFolderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\FileSharing\FileSharingSettingResource\Pages;

use App\Filament\Resources\FileSharing\FileSharingSettingResource;
use Filament\Resources\Pages\ManageRecords;

class ManageFileSharingSettings extends ManageRecords
{
    protected static string $resource = FileSharingSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}

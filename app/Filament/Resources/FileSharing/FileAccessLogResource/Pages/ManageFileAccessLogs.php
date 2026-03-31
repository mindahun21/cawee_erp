<?php

namespace App\Filament\Resources\FileSharing\FileAccessLogResource\Pages;

use App\Filament\Resources\FileSharing\FileAccessLogResource;
use Filament\Resources\Pages\ManageRecords;

class ManageFileAccessLogs extends ManageRecords
{
    protected static string $resource = FileAccessLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}

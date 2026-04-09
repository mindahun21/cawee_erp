<?php

namespace App\Filament\Resources\FileSharing\FileSharingSettingResource\Pages;

use App\Filament\Resources\FileSharing\FileSharingSettingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageFileSharingSettings extends ManageRecords
{
    protected static string $resource = FileSharingSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Add Setting')
                ->modalHeading('Add File Sharing Setting')
                ->successNotificationTitle('Setting created'),
        ];
    }
}

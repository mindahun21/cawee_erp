<?php

namespace App\Filament\Resources\FileSharing\FileShareResource\Pages;

use App\Filament\Resources\FileSharing\FileShareResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageFileShares extends ManageRecords
{
    protected static string $resource = FileShareResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make('create')
                ->successNotificationTitle('Share created successfully'),
        ];
    }
}

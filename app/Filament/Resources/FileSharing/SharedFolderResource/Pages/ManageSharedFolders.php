<?php

namespace App\Filament\Resources\FileSharing\SharedFolderResource\Pages;

use App\Filament\Resources\FileSharing\SharedFolderResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageSharedFolders extends ManageRecords
{
    protected static string $resource = SharedFolderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download_all_folders')
                ->label('Download All Folders')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('warning')
                ->url(route('file-sharing.folders.download-all'))
                ->openUrlInNewTab(),
            CreateAction::make(),
        ];
    }
}

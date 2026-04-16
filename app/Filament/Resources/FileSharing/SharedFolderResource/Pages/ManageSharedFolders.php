<?php

namespace App\Filament\Resources\FileSharing\SharedFolderResource\Pages;

use App\Filament\Resources\FileSharing\SharedFolderResource;
use App\Models\SharedFolder;
use App\Support\FileSharing\FolderArchiveImporter;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\Storage;

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
            Action::make('import_folder_zip')
                ->label('Import Folder ZIP')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('info')
                ->form([
                    Select::make('parent_id')
                        ->label('Parent Folder')
                        ->options(SharedFolder::query()->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->placeholder('Root folder'),
                    Select::make('visibility')
                        ->options([
                            'private' => 'Private',
                            'internal' => 'Internal',
                            'client' => 'Client',
                            'public' => 'Public',
                        ])
                        ->default('internal')
                        ->required(),
                    FileUpload::make('archive')
                        ->label('Folder ZIP')
                        ->disk('local')
                        ->directory('temp-folder-imports')
                        ->acceptedFileTypes([
                            '.zip',
                            'application/zip',
                            'application/x-zip-compressed',
                            'multipart/x-zip',
                        ])
                        ->required()
                        ->helperText('Upload a zip archive containing folders and files. The folder tree will be recreated here.'),
                ])
                ->action(function (array $data): void {
                    $relativePath = is_array($data['archive']) ? array_values($data['archive'])[0] : $data['archive'];
                    $fullPath = Storage::disk('local')->path($relativePath);

                    $result = app(FolderArchiveImporter::class)->import(
                        $fullPath,
                        $data['parent_id'] ?? null,
                        $data['visibility'],
                        auth()->id()
                    );

                    Storage::disk('local')->delete($relativePath);

                    Notification::make()
                        ->title('Folder archive imported')
                        ->body($result['folders'].' folder(s) and '.$result['files'].' file(s) were added.')
                        ->success()
                        ->send();
                }),
            CreateAction::make(),
        ];
    }
}

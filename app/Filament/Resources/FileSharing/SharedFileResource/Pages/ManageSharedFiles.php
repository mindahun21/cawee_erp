<?php

namespace App\Filament\Resources\FileSharing\SharedFileResource\Pages;

use App\Filament\Resources\FileSharing\SharedFileResource;
use App\Models\FileSharingSetting;
use App\Models\SharedFile;
use App\Models\SharedFolder;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;

class ManageSharedFiles extends ManageRecords
{
    protected static string $resource = SharedFileResource::class;

    protected function getHeaderActions(): array
    {
        $maxMb = FileSharingSetting::maxFileSizeMb();
        $acceptedTypes = FileSharingSetting::acceptedUploadTypes();

        return [
            Action::make('upload_multiple')
                ->label('Upload Multiple Files')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('warning')
                ->form([
                    Select::make('folder_id')
                        ->label('Folder')
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
                    FileUpload::make('files')
                        ->label('Files')
                        ->disk(config('filesystems.default'))
                        ->directory('shared-files')
                        ->multiple()
                        ->reorderable(false)
                        ->downloadable()
                        ->openable()
                        ->preserveFilenames()
                        ->acceptedFileTypes($acceptedTypes)
                        ->maxSize($maxMb * 1024)
                        ->required()
                        ->helperText('Upload several files at once. Each file will create its own record.'),
                ])
                ->action(function (array $data): void {
                    $paths = collect($data['files'] ?? [])
                        ->flatten()
                        ->filter()
                        ->values();

                    foreach ($paths as $path) {
                        $basename = pathinfo((string) $path, PATHINFO_BASENAME);
                        $displayName = pathinfo($basename, PATHINFO_FILENAME);

                        SharedFile::query()->create([
                            'folder_id' => $data['folder_id'] ?? null,
                            'display_name' => $displayName,
                            'original_name' => $basename,
                            'disk' => config('filesystems.default'),
                            'path' => $path,
                            'visibility' => $data['visibility'],
                            'is_locked' => false,
                            'uploaded_by' => auth()->id(),
                        ]);
                    }

                    Notification::make()
                        ->title('Files uploaded')
                        ->body($paths->count().' file(s) were added successfully.')
                        ->success()
                        ->send();
                }),
            CreateAction::make(),
        ];
    }
}

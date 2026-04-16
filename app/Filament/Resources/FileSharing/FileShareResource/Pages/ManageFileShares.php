<?php

namespace App\Filament\Resources\FileSharing\FileShareResource\Pages;

use App\Filament\Resources\FileSharing\FileShareResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageFileShares extends ManageRecords
{
    protected static string $resource = FileShareResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('myShares')
                ->label('My Employee Shares')
                ->icon('heroicon-o-folder-open')
                ->outlined()
                ->url(fn (): string => route('recipient-shares.index')),
            CreateAction::make('create')
                ->successNotificationTitle('Share created successfully'),
        ];
    }
}

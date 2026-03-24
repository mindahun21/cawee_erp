<?php

declare(strict_types=1);

namespace App\Filament\Resources\BRT\ProgressUpdateResource\Pages;

use App\Filament\Resources\BRT\ProgressUpdateResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditProgressUpdate extends EditRecord
{
    protected static string $resource = ProgressUpdateResource::class;

    protected function getHeaderActions(): array
    {
        return [ViewAction::make(), DeleteAction::make()];
    }
}

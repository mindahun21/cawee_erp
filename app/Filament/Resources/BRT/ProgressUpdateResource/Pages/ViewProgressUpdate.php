<?php

declare(strict_types=1);

namespace App\Filament\Resources\BRT\ProgressUpdateResource\Pages;

use App\Filament\Resources\BRT\ProgressUpdateResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewProgressUpdate extends ViewRecord
{
    protected static string $resource = ProgressUpdateResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make(), DeleteAction::make()];
    }
}

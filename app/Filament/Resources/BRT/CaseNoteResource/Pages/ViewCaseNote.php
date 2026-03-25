<?php

declare(strict_types=1);

namespace App\Filament\Resources\BRT\CaseNoteResource\Pages;

use App\Filament\Resources\BRT\CaseNoteResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCaseNote extends ViewRecord
{
    protected static string $resource = CaseNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make(), DeleteAction::make()];
    }
}

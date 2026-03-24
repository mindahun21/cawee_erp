<?php

declare(strict_types=1);

namespace App\Filament\Resources\BRT\CaseNoteResource\Pages;

use App\Filament\Resources\BRT\CaseNoteResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCaseNote extends EditRecord
{
    protected static string $resource = CaseNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [ViewAction::make(), DeleteAction::make()];
    }
}

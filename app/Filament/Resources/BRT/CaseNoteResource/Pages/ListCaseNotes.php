<?php

declare(strict_types=1);

namespace App\Filament\Resources\BRT\CaseNoteResource\Pages;

use App\Filament\Resources\BRT\CaseNoteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCaseNotes extends ListRecords
{
    protected static string $resource = CaseNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

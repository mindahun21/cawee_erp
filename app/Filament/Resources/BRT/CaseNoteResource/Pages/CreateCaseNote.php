<?php

declare(strict_types=1);

namespace App\Filament\Resources\BRT\CaseNoteResource\Pages;

use App\Filament\Resources\BRT\CaseNoteResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCaseNote extends CreateRecord
{
    protected static string $resource = CaseNoteResource::class;
}

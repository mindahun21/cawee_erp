<?php

declare(strict_types=1);

namespace App\Filament\Resources\BRT\ProgressUpdateResource\Pages;

use App\Filament\Resources\BRT\ProgressUpdateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProgressUpdates extends ListRecords
{
    protected static string $resource = ProgressUpdateResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

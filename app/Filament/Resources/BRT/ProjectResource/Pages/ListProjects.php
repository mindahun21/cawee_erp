<?php

declare(strict_types=1);

namespace App\Filament\Resources\BRT\ProjectResource\Pages;

use App\Filament\Resources\BRT\ProjectResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProjects extends ListRecords
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Resources\BRT\ProjectResource\Pages;

use App\Filament\Resources\BRT\ProjectResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewProject extends ViewRecord
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Resources\BRT\TrainingEventResource\Pages;

use App\Filament\Resources\BRT\TrainingEventResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTrainingEvent extends ViewRecord
{
    protected static string $resource = TrainingEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}

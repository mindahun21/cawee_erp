<?php

declare(strict_types=1);

namespace App\Filament\Resources\BRT\TrainingEventResource\Pages;

use App\Filament\Resources\BRT\TrainingEventResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTrainingEvents extends ListRecords
{
    protected static string $resource = TrainingEventResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

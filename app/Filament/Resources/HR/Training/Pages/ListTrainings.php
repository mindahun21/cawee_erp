<?php

namespace App\Filament\Resources\HR\Training\Pages;

use App\Filament\Resources\HR\Training\TrainingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTrainings extends ListRecords
{
    protected static string $resource = TrainingResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

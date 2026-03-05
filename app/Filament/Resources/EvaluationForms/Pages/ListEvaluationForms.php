<?php

namespace App\Filament\Resources\EvaluationForms\Pages;

use App\Filament\Resources\EvaluationForms\EvaluationFormResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEvaluationForms extends ListRecords
{
    protected static string $resource = EvaluationFormResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

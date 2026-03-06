<?php

namespace App\Filament\Resources\EvaluationForms\Pages;

use App\Filament\Resources\EvaluationForms\EvaluationFormResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEvaluationForm extends EditRecord
{
    protected static string $resource = EvaluationFormResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\HR\Settings\Pages;

use App\Filament\Resources\HR\Settings\TrainingTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageTrainingTypes extends ManageRecords
{
    protected static string $resource = TrainingTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

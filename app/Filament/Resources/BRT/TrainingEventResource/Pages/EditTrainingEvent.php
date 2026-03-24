<?php

declare(strict_types=1);

namespace App\Filament\Resources\BRT\TrainingEventResource\Pages;

use App\Filament\Resources\BRT\TrainingEventResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditTrainingEvent extends EditRecord
{
    protected static string $resource = TrainingEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}

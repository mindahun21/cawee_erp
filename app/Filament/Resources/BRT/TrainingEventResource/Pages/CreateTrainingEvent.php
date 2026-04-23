<?php

declare(strict_types=1);

namespace App\Filament\Resources\BRT\TrainingEventResource\Pages;

use App\Filament\Resources\BRT\TrainingEventResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTrainingEvent extends CreateRecord
{
    protected static string $resource = TrainingEventResource::class;
}

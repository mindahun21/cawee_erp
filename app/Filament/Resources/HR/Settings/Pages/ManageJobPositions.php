<?php

namespace App\Filament\Resources\HR\Settings\Pages;

use App\Filament\Resources\HR\Settings\JobPositionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageJobPositions extends ManageRecords
{
    protected static string $resource = JobPositionResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

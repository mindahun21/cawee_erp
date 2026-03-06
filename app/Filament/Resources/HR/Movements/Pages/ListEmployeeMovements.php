<?php

namespace App\Filament\Resources\HR\Movements\Pages;

use App\Filament\Resources\HR\Movements\EmployeeMovementResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEmployeeMovements extends ListRecords
{
    protected static string $resource = EmployeeMovementResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

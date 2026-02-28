<?php

namespace App\Filament\Resources\HR\Movements\Pages;

use App\Filament\Resources\HR\Movements\EmployeeMovementResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEmployeeMovement extends EditRecord
{
    protected static string $resource = EmployeeMovementResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}

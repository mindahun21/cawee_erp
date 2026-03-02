<?php

namespace App\Filament\Resources\HR\Movements\Pages;

use App\Filament\Resources\HR\Movements\EmployeeMovementResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEmployeeMovement extends CreateRecord
{
    protected static string $resource = EmployeeMovementResource::class;
}

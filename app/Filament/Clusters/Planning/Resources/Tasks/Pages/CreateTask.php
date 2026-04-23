<?php

namespace App\Filament\Clusters\Planning\Resources\Tasks\Pages;

use App\Filament\Clusters\Planning\Resources\Tasks\TaskResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTask extends CreateRecord
{
    protected static string $resource = TaskResource::class;
}

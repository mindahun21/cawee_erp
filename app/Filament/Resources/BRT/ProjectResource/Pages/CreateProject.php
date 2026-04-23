<?php

declare(strict_types=1);

namespace App\Filament\Resources\BRT\ProjectResource\Pages;

use App\Filament\Resources\BRT\ProjectResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProject extends CreateRecord
{
    protected static string $resource = ProjectResource::class;
}

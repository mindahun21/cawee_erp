<?php

namespace App\Filament\Resources\Settings\EmploymentTypeResource\Pages;

use App\Filament\Resources\Settings\EmploymentTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageEmploymentTypes extends ManageRecords
{
    protected static string $resource = EmploymentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

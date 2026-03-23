<?php

namespace App\Filament\Resources\Settings\GenderResource\Pages;

use App\Filament\Resources\Settings\GenderResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageGenders extends ManageRecords
{
    protected static string $resource = GenderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

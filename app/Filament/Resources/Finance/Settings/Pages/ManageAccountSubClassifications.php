<?php

namespace App\Filament\Resources\Finance\Settings\Pages;

use App\Filament\Resources\Finance\Settings\AccountSubClassificationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageAccountSubClassifications extends ManageRecords
{
    protected static string $resource = AccountSubClassificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Add Sub-Classification'),
        ];
    }
}

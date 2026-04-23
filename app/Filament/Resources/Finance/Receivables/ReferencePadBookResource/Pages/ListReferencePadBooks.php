<?php

namespace App\Filament\Resources\Finance\Receivables\ReferencePadBookResource\Pages;

use App\Filament\Resources\Finance\Receivables\ReferencePadBookResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReferencePadBooks extends ListRecords
{
    protected static string $resource = ReferencePadBookResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

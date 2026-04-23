<?php

namespace App\Filament\Resources\Finance\Receivables\ReferencePadBookResource\Pages;

use App\Filament\Resources\Finance\Receivables\ReferencePadBookResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewReferencePadBook extends ViewRecord
{
    protected static string $resource = ReferencePadBookResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}

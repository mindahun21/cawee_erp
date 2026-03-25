<?php

declare(strict_types=1);

namespace App\Filament\Resources\ME\BeneficiaryResource\Pages;

use App\Filament\Resources\ME\BeneficiaryResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewBeneficiary extends ViewRecord
{
    protected static string $resource = BeneficiaryResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make()];
    }
}

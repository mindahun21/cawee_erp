<?php

declare(strict_types=1);

namespace App\Filament\Resources\ME\BeneficiaryResource\Pages;

use App\Filament\Resources\ME\BeneficiaryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBeneficiary extends EditRecord
{
    protected static string $resource = BeneficiaryResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}

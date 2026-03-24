<?php

declare(strict_types=1);

namespace App\Filament\Resources\BRT\BeneficiaryResource\Pages;

use App\Filament\Resources\BRT\BeneficiaryResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditBeneficiary extends EditRecord
{
    protected static string $resource = BeneficiaryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Resources\BRT\ReferralResource\Pages;

use App\Filament\Resources\BRT\ReferralResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditReferral extends EditRecord
{
    protected static string $resource = ReferralResource::class;

    protected function getHeaderActions(): array
    {
        return [ViewAction::make(), DeleteAction::make()];
    }
}

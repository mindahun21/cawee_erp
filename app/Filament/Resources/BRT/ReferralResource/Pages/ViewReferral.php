<?php

declare(strict_types=1);

namespace App\Filament\Resources\BRT\ReferralResource\Pages;

use App\Filament\Resources\BRT\ReferralResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewReferral extends ViewRecord
{
    protected static string $resource = ReferralResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make(), DeleteAction::make()];
    }
}

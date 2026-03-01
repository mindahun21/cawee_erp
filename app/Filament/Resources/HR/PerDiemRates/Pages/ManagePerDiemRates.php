<?php

namespace App\Filament\Resources\HR\PerDiemRates\Pages;

use App\Filament\Concerns\HasHrSettingsNavigation;
use App\Filament\Resources\HR\PerDiemRates\PerDiemRateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePerDiemRates extends ManageRecords
{
    use HasHrSettingsNavigation;

    protected static string $resource = PerDiemRateResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

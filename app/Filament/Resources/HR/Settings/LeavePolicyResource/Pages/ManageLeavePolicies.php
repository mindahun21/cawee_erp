<?php

namespace App\Filament\Resources\HR\Settings\LeavePolicyResource\Pages;

use App\Filament\Concerns\HasHrSettingsNavigation;
use App\Filament\Resources\HR\Settings\LeavePolicyResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageLeavePolicies extends ManageRecords
{
    use HasHrSettingsNavigation;

    protected static string $resource = LeavePolicyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

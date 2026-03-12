<?php

namespace App\Filament\Resources\Procurement\Settings\Pages;

use App\Filament\Concerns\HasProcurementSettingsNavigation;
use App\Filament\Resources\Procurement\Settings\BidSecurityResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageBidSecurities extends ManageRecords
{
    use HasProcurementSettingsNavigation;

    protected static string $resource = BidSecurityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Add Bid Security'),
        ];
    }
}

<?php

namespace App\Filament\Resources\CampaignEvents\Pages;

use App\Filament\Resources\CampaignEvents\CampaignEventResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCampaignEvent extends ViewRecord
{
    protected static string $resource = CampaignEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}

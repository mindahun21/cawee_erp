<?php

namespace App\Filament\Resources\CampaignEvents\Pages;

use App\Filament\Resources\CampaignEvents\CampaignEventResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCampaignEvents extends ListRecords
{
    protected static string $resource = CampaignEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            CampaignEventResource\Widgets\EventStats::class,
        ];
    }
}

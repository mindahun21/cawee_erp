<?php

namespace App\Filament\Clusters\Planning\Resources\Plans\Pages;

use App\Filament\Clusters\Planning\Resources\Plans\PlanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPlans extends ListRecords
{
    protected static string $resource = PlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Clusters\Planning\Resources\Plans\Widgets\PlanOverview::class,
        ];
    }
}

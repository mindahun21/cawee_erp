<?php

namespace App\Filament\Resources\HR\TravelAdvances\Pages;
use App\Filament\Resources\HR\TravelAdvances\TravelAdvanceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
class ListTravelAdvances extends ListRecords
{
    protected static string $resource = TravelAdvanceResource::class;
    protected function getHeaderActions(): array { return [CreateAction::make()]; }
}

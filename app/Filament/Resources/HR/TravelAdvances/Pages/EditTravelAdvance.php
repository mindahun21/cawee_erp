<?php

namespace App\Filament\Resources\HR\TravelAdvances\Pages;
use App\Filament\Resources\HR\TravelAdvances\TravelAdvanceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
class EditTravelAdvance extends EditRecord
{
    protected static string $resource = TravelAdvanceResource::class;
    protected function getHeaderActions(): array { return [DeleteAction::make()]; }
}

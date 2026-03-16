<?php

namespace App\Filament\Resources\HR\Timesheets\TimesheetResource\Pages;

use App\Filament\Resources\HR\Timesheets\TimesheetResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTimesheets extends ListRecords
{
    protected static string $resource = TimesheetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

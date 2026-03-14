<?php

namespace App\Filament\Resources\HR\Timesheets\LeaveTypeResource\Pages;

use App\Filament\Resources\HR\Timesheets\LeaveTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageLeaveTypes extends ManageRecords
{
    protected static string $resource = LeaveTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

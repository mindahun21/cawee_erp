<?php

namespace App\Filament\Resources\HR\Timesheets\TimesheetResource\Pages;

use App\Filament\Resources\HR\Timesheets\TimesheetResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTimesheet extends EditRecord
{
    protected static string $resource = TimesheetResource::class;

    private ?array $timesheetData = null;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Extract timesheet_data so it doesn't hit the DB update
        $this->timesheetData = $data['timesheet_data'] ?? null;
        unset($data['timesheet_data']);

        return $data;
    }

    protected function afterSave(): void
    {
        // Persist grid data after the parent record is updated
        if ($this->timesheetData) {
            $this->record->saveTimesheetData($this->timesheetData);
        }
    }
}

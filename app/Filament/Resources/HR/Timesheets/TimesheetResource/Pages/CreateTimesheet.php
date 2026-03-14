<?php

namespace App\Filament\Resources\HR\Timesheets\TimesheetResource\Pages;

use App\Filament\Resources\HR\Timesheets\TimesheetResource;
use App\Models\HrTimesheet;
use Filament\Resources\Pages\CreateRecord;

class CreateTimesheet extends CreateRecord
{
    protected static string $resource = TimesheetResource::class;
 
    public function mount(): void
    {
        parent::mount();
 
        $employeeId = request()->query('employee_id');
        $month = request()->query('month') ?? date('n');
        $year = request()->query('year') ?? date('Y');

        $this->form->fill([
            'employee_id' => $employeeId,
            'month' => $month,
            'year' => $year,
            'timesheet_data' => HrTimesheet::generatePreviewData($employeeId, $month, $year),
        ]);
    }

    private ?array $timesheetData = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Extract timesheet_data so it doesn't hit the DB insert
        $this->timesheetData = $data['timesheet_data'] ?? null;
        unset($data['timesheet_data']);

        return $data;
    }

    protected function afterCreate(): void
    {
        // Now that the record exists, persist the grid data
        if ($this->timesheetData) {
            $this->record->saveTimesheetData($this->timesheetData);
        }
    }
}

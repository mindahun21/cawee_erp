<?php

namespace App\Filament\Resources\HR\LeaveRequests\LeaveBalanceReportResource\Pages;

use App\Filament\Resources\HR\LeaveRequests\LeaveBalanceReportResource;
use App\Models\Employee;
use App\Services\HR\LeaveBalanceService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Storage;

class ListLeaveBalanceReports extends ListRecords
{
    protected static string $resource = LeaveBalanceReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('import_leave_balance')
                ->label('Import Balances')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->modalHeading('Import Leave Balances')
                ->modalDescription('Upload an Excel file to import historical leave balances. Format: Row 1 Header -> "Employee ID" | 2016 | 2017... The column values should be the REMAINING balance for that Ethiopian fiscal year.')
                ->modalSubmitActionLabel('Import Now')
                ->form([
                    FileUpload::make('file')
                        ->label('Excel File (.xlsx)')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                        ])
                        ->disk('local')
                        ->directory('leave-imports')
                        ->required(),
                ])
                ->action(function (array $data, Action $action) {
                    // Filament FileUpload uses the 'local' (private) disk by default
                    // Files land in storage/app/private/{directory}/{filename}
                    $path = Storage::disk('local')->path($data['file']);

                    if (! file_exists($path)) {
                        Notification::make()->danger()->title('Uploaded file not found. Please try again.')->send();
                        return;
                    }

                    $svc          = new LeaveBalanceService();
                    $created      = 0;
                    $importErrors = [];

                    try {
                        $spreadsheet = IOFactory::load($path);
                        // Always read the FIRST sheet (index 0), not getActiveSheet()
                        // which returns whichever sheet was last active when saved
                        $sheet       = $spreadsheet->getSheet(0);
                        $rows        = $sheet->toArray();

                        if (empty($rows)) {
                            Notification::make()->danger()->title('The spreadsheet is empty.')->send();
                            return;
                        }

                        $headers = array_shift($rows);
                        $yearColumnMap = [];
                        foreach ($headers as $colIndex => $header) {
                            if (is_numeric($header)) {
                                $yearColumnMap[(int) $header] = $colIndex;
                            }
                        }

                        if (empty($yearColumnMap)) {
                            Notification::make()->danger()->title('No year columns found in header. Expected numeric headers like 2016, 2017.')->send();
                            return;
                        }

                        foreach ($rows as $rowIndex => $row) {
                            $employeeIdentifier = trim((string) ($row[0] ?? ''));
                            if ($employeeIdentifier === '') continue;

                            $employee = Employee::find($employeeIdentifier)
                                ?? Employee::where('first_name', 'LIKE', "%{$employeeIdentifier}%")->first();

                            if (! $employee) {
                                $importErrors[] = "Row " . ($rowIndex + 2) . ": Employee '{$employeeIdentifier}' not found.";
                                continue;
                            }

                            $fiscalYearMap = [];
                            foreach ($yearColumnMap as $year => $colIndex) {
                                $val = $row[$colIndex] ?? null;
                                if ($val !== null && $val !== '') {
                                    $fiscalYearMap[$year] = (float) $val;
                                }
                            }

                            if (empty($fiscalYearMap)) continue;

                            $res          = $svc->importBalanceFromExcel($employee, $fiscalYearMap);
                            $created     += $res['created'];
                            $importErrors = array_merge($importErrors, $res['errors']);
                        }

                        // Cleanup file
                        @unlink($path);

                        $msg = "Import complete. Created {$created} historical leave requests.";
                        if (!empty($importErrors)) {
                            $msg .= " Errors on " . count($importErrors) . " row(s): " . implode('; ', array_slice($importErrors, 0, 3));
                            Notification::make()->warning()->title('Import finished with errors')->body($msg)->send();
                        } else {
                            Notification::make()->success()->title('Import Successful ✓')->body($msg)->send();
                        }

                    } catch (\Throwable $e) {
                        Notification::make()->danger()->title('Import Failed')->body($e->getMessage())->send();
                    }
                }),
        ];
    }
}

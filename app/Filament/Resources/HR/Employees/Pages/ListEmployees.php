<?php

namespace App\Filament\Resources\HR\Employees\Pages;

use App\Exports\EmployeeExport;
use App\Filament\Resources\HR\Employees\EmployeeResource;
use App\Imports\EmployeeImport;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ListEmployees extends ListRecords
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // ── Export Excel ─────────────────────────────────────────
            Action::make('export_excel')
                ->label('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(fn () => Excel::download(new EmployeeExport(), 'employees-' . now()->format('Ymd') . '.xlsx')),

            // ── Export CSV ───────────────────────────────────────────
            Action::make('export_csv')
                ->label('Export CSV')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->action(fn () => Excel::download(new EmployeeExport(), 'employees-' . now()->format('Ymd') . '.csv', \Maatwebsite\Excel\Excel::CSV)),

            // ── Download Template ────────────────────────────────────
            Action::make('download_template')
                ->label('Download Import Template')
                ->icon('heroicon-o-document-arrow-down')
                ->color('info')
                ->action(function () {
                    $headings = [
                        ['first_name', 'last_name', 'email', 'phone_number', 'gender', 'date_of_birth',
                         'national_id', 'tin', 'pension_id', 'department', 'job_position', 'contract_type',
                         'employment_type', 'date_of_employment', 'education_level', 'field_of_study',
                         'basic_salary', 'transport_allowance', 'house_allowance', 'bank_account_awash',
                         'location', 'project', 'remarks'],
                        ['John', 'Doe', 'john.doe@example.com', '912345678', 'Male', '1990-05-15',
                         'ETH001', 'TIN001', 'PEN001', 'Human Resources', 'HR Officer', 'Permanent',
                         'Permanent', '2023-01-15', "Bachelor's Degree", 'Human Resource Management',
                         15000, 2000, 1000, '0123456789', 'Addis Ababa Office', 'Project Alpha', 'Sample row'],
                    ];
                    return Excel::download(
                        new class($headings) implements \Maatwebsite\Excel\Concerns\FromArray {
                            public function __construct(private array $data) {}
                            public function array(): array { return $this->data; }
                        },
                        'employee-import-template.xlsx'
                    );
                }),

            // ── Import ───────────────────────────────────────────────
            Action::make('import')
                ->label('Import Employees')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('warning')
                ->form([
                    FileUpload::make('file')
                        ->label('Excel / CSV File')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                            'text/csv',
                        ])
                        ->required()
                        ->helperText('Download the template first, fill it in, then upload here.'),
                ])
                ->action(function (array $data) {
                    // Livewire FileUpload stores files in livewire-tmp/ on the default disk (local = storage/app/)
                    $relativePath = is_array($data['file']) ? array_values($data['file'])[0] : $data['file'];

                    // Try the local disk first (where livewire-tmp lives), then fall back to public disk
                    if (Storage::disk('local')->exists('livewire-tmp/' . $relativePath)) {
                        $path = Storage::disk('local')->path('livewire-tmp/' . $relativePath);
                    } elseif (Storage::disk('local')->exists($relativePath)) {
                        $path = Storage::disk('local')->path($relativePath);
                    } elseif (Storage::disk('public')->exists($relativePath)) {
                        $path = Storage::disk('public')->path($relativePath);
                    } else {
                        Notification::make()
                            ->title('File Not Found')
                            ->body('The uploaded file could not be located. Please try uploading again.')
                            ->danger()
                            ->send();
                        return;
                    }

                    $importer = new EmployeeImport();
                    Excel::import($importer, $path);

                    $body = "✅ {$importer->importedCount} imported";
                    if ($importer->skippedCount > 0) {
                        $body .= ", ⚠️ {$importer->skippedCount} skipped (duplicates or errors)";
                    }
                    if (! empty($importer->errors)) {
                        $body .= "\n" . implode("\n", array_slice($importer->errors, 0, 5));
                    }

                    Notification::make()
                        ->title("Import Complete — {$importer->importedCount} employees added")
                        ->body($body)
                        ->success()
                        ->send();

                    // Clean up temporary file
                    @unlink($path);
                }),

            CreateAction::make(),
        ];
    }
}

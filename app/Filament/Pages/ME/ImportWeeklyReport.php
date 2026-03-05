<?php

namespace App\Filament\Pages\ME;

use App\Services\ME\WeeklyReportImportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ImportWeeklyReport extends Page implements HasForms
{
    protected \Filament\Support\Enums\Width | string | null $maxContentWidth = \Filament\Support\Enums\Width::Full;
    use InteractsWithForms;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static string | \UnitEnum | null $navigationGroup = 'Monitoring and Evaluation';

    protected static ?string $navigationLabel = 'Import Periodic Report';

    protected static ?int $navigationSort = 7;

    protected string $view = 'filament.pages.me.import-weekly-report';

    public ?array $data = [];

    public bool $excelImportEnabled = false;

    /** @var array<string, mixed> */
    public array $lastImportSummary = [];

    /** @var array<int, array<string, mixed>> */
    public array $rejectedRows = [];

    /** @var array<int, array<string, mixed>> */
    public array $acceptedRowsPreview = [];

    public ?string $rejectedRowsCsvPath = null;

    public ?string $debugReportPath = null;

    public function mount(): void
    {
        $this->excelImportEnabled = (bool) config('me.features.excel_import', true)
            && class_exists(\Maatwebsite\Excel\Facades\Excel::class);

        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                FileUpload::make('file')
                    ->required()
                    ->multiple(false)
                    ->acceptedFileTypes([
                        'text/csv',
                        'application/csv',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ])
                    ->directory('me-imports'),
            ])
            ->statePath('data');
    }

    public function import(): void
    {
        if (! $this->excelImportEnabled) {
            Notification::make()
                ->title('Excel import not enabled. Run composer require maatwebsite/excel')
                ->warning()
                ->send();

            return;
        }

        $state = $this->form->getState();
        $file = $this->extractFilePath($state['file'] ?? ($this->data['file'] ?? null));

        if (! $file) {
            Notification::make()
                ->title('Select a file to import.')
                ->danger()
                ->send();

            return;
        }

        $disk = config('filament.default_filesystem_disk', config('filesystems.default'));

        if ($file instanceof TemporaryUploadedFile) {
            $file = $file->store('me-imports', $disk);
        }

        if (! is_string($file) || ($file === '')) {
            Notification::make()
                ->title('Select a file to import.')
                ->danger()
                ->send();

            return;
        }

        $path = Storage::disk($disk)->path($file);

        try {
            $result = app(WeeklyReportImportService::class)->import($path);

            if (is_int($result)) {
                $result = [
                    'rows_processed' => $result,
                    'indicators_created' => 0,
                    'targets_upserted' => 0,
                    'reports_upserted' => $result,
                    'projects_created' => 0,
                    'periods_created' => 0,
                    'warnings' => [],
                    'debug_report' => null,
                ];
            }

            $rowsProcessed = (int) ($result['rows_processed'] ?? 0);
            $indicatorsCreated = (int) ($result['indicators_created'] ?? 0);
            $targetsUpserted = (int) ($result['targets_upserted'] ?? 0);
            $reportsUpserted = (int) ($result['reports_upserted'] ?? 0);
            $projectsCreated = (int) ($result['projects_created'] ?? 0);
            $periodsCreated = (int) ($result['periods_created'] ?? 0);
            $rowsSkipped = (int) ($result['rows_skipped'] ?? 0);
            $rowsFailed = (int) ($result['rows_failed'] ?? 0);
            $debugReport = $result['debug_report'] ?? null;
            $rejectedRows = array_values($result['rejected_rows'] ?? []);
            $acceptedRowsPreview = array_values($result['accepted_rows_preview'] ?? []);
            $targetsCreated = (int) ($result['targets_created'] ?? 0);
            $targetsUpdated = (int) ($result['targets_updated'] ?? 0);
            $targetsUnchanged = (int) ($result['targets_unchanged'] ?? 0);
            $reportsCreated = (int) ($result['reports_created'] ?? 0);
            $reportsUpdated = (int) ($result['reports_updated'] ?? 0);
            $reportsUnchanged = (int) ($result['reports_unchanged'] ?? 0);
            $duplicateTargetsInFile = (int) ($result['duplicate_target_rows_in_file'] ?? 0);
            $duplicateReportsInFile = (int) ($result['duplicate_report_rows_in_file'] ?? 0);
            $duplicateRowsInFile = (int) ($result['duplicate_rows_in_file'] ?? 0);
            $warningsCount = count($result['warnings'] ?? []);
            $duplicateTotal = $duplicateRowsInFile > 0
                ? $duplicateRowsInFile
                : ($duplicateTargetsInFile + $duplicateReportsInFile);
            $createdTotal = $targetsCreated + $reportsCreated;
            $updatedTotal = $targetsUpdated + $reportsUpdated;
            $unchangedTotal = $targetsUnchanged + $reportsUnchanged;
            $rejectedCount = count($rejectedRows);

            $this->lastImportSummary = [
                'rows_total' => (int) ($result['rows_total'] ?? 0),
                'rows_processed' => $rowsProcessed,
                'rows_skipped' => $rowsSkipped,
                'rows_failed' => $rowsFailed,
                'indicators_created' => $indicatorsCreated,
                'targets_upserted' => $targetsUpserted,
                'targets_created' => $targetsCreated,
                'targets_updated' => $targetsUpdated,
                'targets_unchanged' => $targetsUnchanged,
                'reports_upserted' => $reportsUpserted,
                'reports_created' => $reportsCreated,
                'reports_updated' => $reportsUpdated,
                'reports_unchanged' => $reportsUnchanged,
                'projects_created' => $projectsCreated,
                'periods_created' => $periodsCreated,
                'metrics_detected' => (int) ($result['metrics_detected'] ?? 0),
                'duplicate_rows_in_file' => $duplicateRowsInFile,
                'duplicate_target_rows_in_file' => $duplicateTargetsInFile,
                'duplicate_report_rows_in_file' => $duplicateReportsInFile,
            ];
            $this->rejectedRows = array_slice($rejectedRows, 0, 200);
            $this->acceptedRowsPreview = array_slice($acceptedRowsPreview, 0, 200);
            $this->rejectedRowsCsvPath = is_string($result['rejected_rows_csv'] ?? null) ? $result['rejected_rows_csv'] : null;
            $this->debugReportPath = is_string($debugReport) ? $debugReport : null;

            $lines = [
                "Processed: {$rowsProcessed} | Rejected: {$rejectedCount} | Failed: {$rowsFailed}",
                "Created: Targets {$targetsCreated}, Reports {$reportsCreated} | Updated: Targets {$targetsUpdated}, Reports {$reportsUpdated}",
                "Unchanged: Targets {$targetsUnchanged}, Reports {$reportsUnchanged}",
            ];

            if (($createdTotal === 0) && ($updatedTotal === 0) && ($unchangedTotal > 0)) {
                $lines[] = 'No DB changes were made. File appears already imported.';
            } elseif (($createdTotal === 0) && ($updatedTotal > 0)) {
                $lines[] = 'No new records were created. Existing period records were updated.';
            }

            if ($duplicateTotal > 0) {
                $lines[] = "Duplicate rows in file: {$duplicateTotal} (duplicate identity rows were ignored).";
            }
            $lines[] = 'See "Import Diagnostics" below for full details and downloads.';

            $notification = Notification::make()
                ->body(implode(PHP_EOL, $lines));

            $hasErrors = ($rowsFailed > 0) || ($rejectedCount > 0);
            $needsReview = ($duplicateTotal > 0)
                || ($warningsCount > 0)
                || (($createdTotal === 0) && ($updatedTotal === 0) && ($unchangedTotal > 0));

            if ($hasErrors) {
                $notification
                    ->title("Import completed with issues. {$rowsProcessed} row(s) processed.")
                    ->danger();
            } elseif ($needsReview) {
                $notification
                    ->title("Import completed. Review diagnostics. {$rowsProcessed} row(s) processed.")
                    ->warning();
            } else {
                $notification
                    ->title("Import completed. {$rowsProcessed} row(s) processed.")
                    ->success();
            }

            $notification->send();

            $this->form->fill();
        } catch (Throwable $exception) {
            $reason = trim($exception->getMessage()) !== ''
                ? $exception->getMessage()
                : 'Import stopped before completion.';

            $this->lastImportSummary = [
                'rows_total' => 0,
                'rows_processed' => 0,
                'rows_skipped' => 1,
                'rows_failed' => 1,
                'indicators_created' => 0,
                'targets_upserted' => 0,
                'targets_created' => 0,
                'targets_updated' => 0,
                'targets_unchanged' => 0,
                'reports_upserted' => 0,
                'reports_created' => 0,
                'reports_updated' => 0,
                'reports_unchanged' => 0,
                'projects_created' => 0,
                'periods_created' => 0,
                'metrics_detected' => 0,
                'duplicate_rows_in_file' => 0,
                'duplicate_target_rows_in_file' => 0,
                'duplicate_report_rows_in_file' => 0,
            ];

            $this->rejectedRows = [[
                'row' => '-',
                'project_name' => '-',
                'project_code' => '-',
                'period' => '-',
                'reason' => $reason,
            ]];
            $this->acceptedRowsPreview = [];
            $this->rejectedRowsCsvPath = null;
            $this->debugReportPath = null;

            Notification::make()
                ->title('Import failed')
                ->body($reason)
                ->danger()
                ->send();
        }
    }

    private function extractFilePath(mixed $fileState): mixed
    {
        if ($fileState instanceof TemporaryUploadedFile) {
            return $fileState;
        }

        if (is_string($fileState) && ($fileState !== '')) {
            return $fileState;
        }

        if (is_array($fileState)) {
            foreach ($fileState as $value) {
                $resolved = $this->extractFilePath($value);

                if ($resolved !== null) {
                    return $resolved;
                }
            }
        }

        return null;
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function quickSteps(): array
    {
        return [
            [
                'step' => 'Step 1',
                'title' => 'Download template',
                'details' => 'Start from the provided template so headers match what the importer expects.',
            ],
            [
                'step' => 'Step 2',
                'title' => 'Fill one row per project-indicator-target-period',
                'details' => 'Use one row for each project + indicator + target + period with planned and actual columns.',
            ],
            [
                'step' => 'Step 3',
                'title' => 'Upload and import',
                'details' => 'Upload and import. Exact same file hash is blocked; one report is allowed per target per reporting period.',
            ],
            [
                'step' => 'Step 4',
                'title' => 'Review diagnostics',
                'details' => 'If anything is rejected, download rejected CSV/debug JSON and correct only failed rows.',
            ],
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function schemaColumns(): array
    {
        return [
            ['area' => 'Project', 'column' => 'project_code', 'required' => 'Yes', 'description' => 'Project identity. Existing project code is reused; if missing in system, new project is created.'],
            ['area' => 'Project', 'column' => 'project_name', 'required' => 'Yes', 'description' => 'Project name for validation and creation.'],
            ['area' => 'Project', 'column' => 'project_start_date', 'required' => 'Yes', 'description' => 'Project start date for project profile validation.'],
            ['area' => 'Project', 'column' => 'project_end_date', 'required' => 'Yes', 'description' => 'Project end date for project profile validation.'],

            ['area' => 'Indicator', 'column' => 'indicator_name', 'required' => 'Yes', 'description' => 'Row-level indicator this period row belongs to. One indicator can have many targets.'],
            ['area' => 'Indicator', 'column' => 'framework_type', 'required' => 'Yes', 'description' => 'Allowed: output, outcome, impact.'],
            ['area' => 'Indicator', 'column' => 'frequency', 'required' => 'Yes', 'description' => 'Allowed: weekly, monthly, quarterly, semiannual, annual.'],
            ['area' => 'Indicator', 'column' => 'unit', 'required' => 'Yes', 'description' => 'Indicator unit (for example count, ETB, percent).'],
            ['area' => 'Indicator', 'column' => 'indicator_threshold_warning', 'required' => 'Yes', 'description' => 'Warning threshold (0-100).'],
            ['area' => 'Indicator', 'column' => 'indicator_threshold_critical', 'required' => 'Yes', 'description' => 'Critical threshold (0-100, cannot exceed warning).'],

            ['area' => 'Target', 'column' => 'period_start', 'required' => 'Yes', 'description' => 'Period start date for targets and reports.'],
            ['area' => 'Target', 'column' => 'period_end', 'required' => 'Yes', 'description' => 'Period end date for targets and reports.'],
            ['area' => 'Target', 'column' => 'target_name', 'required' => 'Yes', 'description' => 'Target/segment name under the selected indicator (example: Women Amount, Disbursed Amount).'],
            ['area' => 'Target', 'column' => 'planned_value', 'required' => 'Yes', 'description' => 'Planned numeric value for this target row.'],

            ['area' => 'Report', 'column' => 'actual_value', 'required' => 'No (optional)', 'description' => 'Actual/report numeric value for the same target row. Leave blank to import target only.'],
            ['area' => 'Report', 'column' => 'report_time', 'required' => 'Optional', 'description' => 'Optional report timestamp (date/time). If blank, system time is used.'],
            ['area' => 'Report', 'column' => 'report_description', 'required' => 'Optional', 'description' => 'Clear report note/description for this row.'],
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function validationRules(): array
    {
        return [
            [
                'rule' => 'Project profile columns are mandatory: project_code, project_name, project_start_date, project_end_date.',
                'impact' => 'Row is rejected when any project profile value is missing or invalid.',
            ],
            [
                'rule' => 'Period columns are mandatory: period_start and period_end.',
                'impact' => 'Row is rejected when period dates are missing, invalid, or end is before start.',
            ],
            [
                'rule' => 'Indicator columns are mandatory: indicator_name, framework_type, frequency, unit.',
                'impact' => 'Row is rejected when missing or outside allowed framework/frequency values.',
            ],
            [
                'rule' => 'Indicator thresholds are mandatory: indicator_threshold_warning and indicator_threshold_critical.',
                'impact' => 'Row is rejected when thresholds are non-numeric, outside 0-100, or critical > warning.',
            ],
            [
                'rule' => 'Target columns are mandatory: target_name, planned_value.',
                'impact' => 'Row is rejected when target_name is missing or planned_value is non-numeric/missing.',
            ],
            [
                'rule' => 'Metric values must be numeric and within -999,999,999,999.99 to 999,999,999,999.99.',
                'impact' => 'Out-of-range or non-numeric metric values are rejected with reason.',
            ],
            [
                'rule' => 'Indicator code is automatic from project_code + indicator_name.',
                'impact' => 'No indicator_code column is required in file.',
            ],
            [
                'rule' => 'actual_value is optional per row.',
                'impact' => 'If blank, only target is written; report is not created/updated for that row.',
            ],
            [
                'rule' => 'One row identity is project_code + indicator_name + target_name + period_start + period_end.',
                'impact' => 'Duplicate identity rows in same file are ignored.',
            ],
            [
                'rule' => 'Exact duplicate file hash is blocked.',
                'impact' => 'Re-importing the exact same file is rejected before row processing.',
            ],
            [
                'rule' => 'Only one report is allowed per target per period (weekly/monthly/quarterly/semiannual/annual).',
                'impact' => 'If a report already exists for the same project+indicator+target+period, row is rejected.',
            ],
            [
                'rule' => 'report_description is optional.',
                'impact' => 'When provided, it is saved as report comment/notes.',
            ],
            [
                'rule' => 'report_time is optional.',
                'impact' => 'If provided, it is saved as report entered/report time; otherwise system time is used.',
            ],
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function commonIssues(): array
    {
        return [
            [
                'issue' => 'Project code exists but row rejected',
                'fix' => 'Ensure project_name, project_start_date, and project_end_date match existing project profile for that code.',
            ],
            [
                'issue' => 'Indicator not created for new row',
                'fix' => 'Provide mandatory indicator columns: indicator_name, framework_type, frequency, unit, indicator_threshold_warning, indicator_threshold_critical.',
            ],
            [
                'issue' => 'Too many duplicates',
                'fix' => 'Keep one row per project + indicator + target + period.',
            ],
            [
                'issue' => 'Need to import many targets in one row',
                'fix' => 'Repeat rows with same project + indicator + period and different target_name values.',
            ],
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function schemaMappings(): array
    {
        return [
            [
                'accepted_columns' => 'project_code, project_name, project_start_date, project_end_date',
                'import_area' => 'Project',
                'how_used' => 'Project is reused by project_code; if not found, a new project is created using this profile.',
            ],
            [
                'accepted_columns' => 'indicator_name, framework_type, frequency, unit, indicator_threshold_warning, indicator_threshold_critical',
                'import_area' => 'Indicator',
                'how_used' => 'Row indicator is resolved/created from indicator_name; indicator_code is auto-generated and locked.',
            ],
            [
                'accepted_columns' => 'period_start, period_end, target_name, planned_value',
                'import_area' => 'Target',
                'how_used' => 'Planned numeric value writes to target for that target_name.',
            ],
            [
                'accepted_columns' => 'actual_value, report_description',
                'import_area' => 'Report',
                'how_used' => 'When actual_value is provided, report is written for same indicator/period/project/target; report_time and report_description are optional.',
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    public function identityRules(): array
    {
        return [
            'Project code identifies the project. It does not become an indicator.',
            'indicator_name identifies the indicator for the row.',
            'target_name identifies the target under that indicator.',
            'planned_value writes target and actual_value writes report for the same row identity (if actual_value is provided).',
            'Planned values go to Target. Actual values go to Report.',
        ];
    }

    public function downloadSchemaTemplate(): StreamedResponse
    {
        return $this->downloadAcceptedColumnsCsv();
    }

    public function downloadSchemaMapping(): StreamedResponse
    {
        return $this->downloadSchemaMappingPdf();
    }

    public function downloadAcceptedColumnsCsv(): StreamedResponse
    {
        $rows = [
            $this->acceptedColumnHeaders(),
            [
                'SVOET-003',
                'SVOET Project 003',
                '2025-01-01',
                '2026-12-31',
                'Weekly Success',
                'output',
                'monthly',
                'count',
                '70',
                '50',
                '2026-03-02',
                '2026-03-08',
                'Women Amount',
                '80',
                '70',
                '2026-03-08 14:30:00',
                'Women group weekly progress note',
            ],
            [
                'SVOET-003',
                'SVOET Project 003',
                '2025-01-01',
                '2026-12-31',
                'Weekly Success',
                'output',
                'monthly',
                'count',
                '70',
                '50',
                '2026-03-02',
                '2026-03-08',
                'Disbursed Amount',
                '50000',
                '',
                '',
                'Target imported without report actual yet',
            ],
        ];

        return $this->streamCsvDownload($rows, 'me_periodic_import_template_v3_row_based.csv');
    }

    public function downloadSchemaMappingPdf(): StreamedResponse
    {
        $pdf = Pdf::loadView('filament.pages.me.weekly-import-mapping-pdf', [
            'columns' => $this->schemaColumns(),
            'mappings' => $this->schemaMappings(),
            'validationRules' => $this->validationRules(),
            'commonIssues' => $this->commonIssues(),
        ])->setPaper('a4', 'portrait');

        $content = $pdf->output();

        return response()->streamDownload(function () use ($content): void {
            echo $content;
        }, 'me_periodic_import_mapping.pdf', [
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function downloadRejectedRows(): ?StreamedResponse
    {
        if (! is_string($this->rejectedRowsCsvPath) || ($this->rejectedRowsCsvPath === '') || (! is_file($this->rejectedRowsCsvPath))) {
            Notification::make()
                ->title('Rejected rows file is not available.')
                ->warning()
                ->send();

            return null;
        }

        return response()->download($this->rejectedRowsCsvPath, basename($this->rejectedRowsCsvPath));
    }

    public function downloadDebugReport(): ?StreamedResponse
    {
        if (! is_string($this->debugReportPath) || ($this->debugReportPath === '') || (! is_file($this->debugReportPath))) {
            Notification::make()
                ->title('Debug report file is not available.')
                ->warning()
                ->send();

            return null;
        }

        return response()->download($this->debugReportPath, basename($this->debugReportPath));
    }

    /**
     * @param  array<int, array<int, string>>  $rows
     */
    private function streamCsvDownload(array $rows, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');

            if (! is_resource($handle)) {
                return;
            }

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function acceptedColumnHeaders(): array
    {
        return [
            'project_code',
            'project_name',
            'project_start_date',
            'project_end_date',
            'indicator_name',
            'framework_type',
            'frequency',
            'unit',
            'indicator_threshold_warning',
            'indicator_threshold_critical',
            'period_start',
            'period_end',
            'target_name',
            'planned_value',
            'actual_value',
            'report_time',
            'report_description',
        ];
    }
}

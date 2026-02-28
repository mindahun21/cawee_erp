<?php

namespace App\Filament\Pages\ME;

use App\Services\ME\WeeklyReportImportService;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Throwable;

class ImportWeeklyReport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static string | \UnitEnum | null $navigationGroup = 'M&E';

    protected static ?string $navigationLabel = 'Import Weekly Report';

    protected static ?int $navigationSort = 7;

    protected string $view = 'filament.pages.me.import-weekly-report';

    public ?array $data = [];

    public bool $excelImportEnabled = false;

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
            $warnings = collect($result['warnings'] ?? [])->take(5)->all();
            $debugReport = $result['debug_report'] ?? null;

            $lines = [
                "Rows processed: {$rowsProcessed}",
                "Indicators created: {$indicatorsCreated}",
                "Targets upserted: {$targetsUpserted}",
                "Reports upserted: {$reportsUpserted}",
                "Projects created: {$projectsCreated}",
                "Periods created: {$periodsCreated}",
            ];

            if (count($warnings) > 0) {
                $lines[] = 'Warnings:';
                foreach ($warnings as $warning) {
                    $lines[] = "- {$warning}";
                }
            }

            if (is_string($debugReport) && ($debugReport !== '')) {
                $lines[] = "Debug report: {$debugReport}";
            }

            Notification::make()
                ->title("Weekly import complete. {$rowsProcessed} row(s) processed.")
                ->body(implode(PHP_EOL, $lines))
                ->success()
                ->send();

            $this->form->fill();
        } catch (Throwable $exception) {
            Notification::make()
                ->title('Import failed')
                ->body($exception->getMessage())
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
}

<?php

namespace App\Filament\Helpers;

use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\ActionGroup;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeSheet;

class ExportHelper
{
    public static function makeBulkAction(string $name = 'export'): \Filament\Actions\BulkActionGroup
    {
        return \Filament\Actions\BulkActionGroup::make([
            self::csvAction('export_csv', true),
            self::excelAction('export_excel', true),
            self::pdfAction('export_pdf', true),
            self::printAction('export_print', true),
        ])
        ->label('Export')
        ->icon('heroicon-o-arrow-down-tray');
    }

    protected static function formatRecords($records, $columns): array
    {
        $data = [];
        foreach ($records as $record) {
            $row = [];
            foreach ($columns as $column) {
                $colName = $column->getName();
                if (in_array($colName, ['action', 'actions'])) continue;
                $val = data_get($record, str_replace('.', '->', $colName));
                if (is_array($val)) $val = implode(', ', $val);
                if ($val instanceof \Carbon\Carbon) $val = $val->format('Y-m-d');
                if ($val === true) $val = 'Yes';
                if ($val === false) $val = 'No';
                $row[] = $val ?? '';
            }
            $data[] = $row;
        }
        return $data;
    }

    protected static function getHeaders($columns): array
    {
        $headers = [];
        foreach ($columns as $column) {
            $colName = $column->getName();
            if (in_array($colName, ['action', 'actions'])) continue;
            $headers[] = $column->getLabel() ?? ucfirst(str_replace('_', ' ', $colName));
        }
        return $headers;
    }

    protected static function getActiveFiltersString($livewire): string
    {
        if (!property_exists($livewire, 'tableFilters')) return 'None';
        $state = $livewire->tableFilters ?? [];
        $active = [];
        foreach ($state as $key => $val) {
            if (is_array($val) && array_filter($val)) {
                $active[] = ucfirst($key) . ': ' . json_encode(array_filter($val));
            } elseif ($val && !is_array($val)) {
                $active[] = ucfirst($key) . ': ' . $val;
            }
        }
        return count($active) > 0 ? implode(' | ', $active) : 'None';
    }

    // ─── CSV ────────────────────────────────────────────────────────────────

    protected static function csvAction(string $name, bool $isBulk)
    {
        if ($isBulk) {
            return BulkAction::make($name)
                ->label('Download CSV')
                ->icon('heroicon-o-document-text')
                ->accessSelectedRecords()
                ->action(function ($livewire, $records) {
                    $columns = $livewire->getTable()->getColumns();
                    $headers = self::getHeaders($columns);
                    $data = self::formatRecords($records, $columns);
                    $filterStr = self::getActiveFiltersString($livewire);
                    return response()->streamDownload(function () use ($headers, $data, $filterStr) {
                        $f = fopen('php://output', 'w');
                        fputcsv($f, ['Active Filters:', $filterStr]);
                        fputcsv($f, []);
                        fputcsv($f, $headers);
                        foreach ($data as $row) fputcsv($f, $row);
                        fclose($f);
                    }, 'export-' . date('Y-m-d') . '.csv', ['Content-Type' => 'text/csv']);
                });
        }

        return Action::make($name)
            ->label('Download CSV')
            ->icon('heroicon-o-document-text')
            ->action(function ($livewire) {
                $columns = $livewire->getTable()->getColumns();
                $headers = self::getHeaders($columns);
                $data = self::formatRecords($livewire->getFilteredTableQuery()->get(), $columns);
                $filterStr = self::getActiveFiltersString($livewire);
                return response()->streamDownload(function () use ($headers, $data, $filterStr) {
                    $f = fopen('php://output', 'w');
                    fputcsv($f, ['Active Filters:', $filterStr]);
                    fputcsv($f, []);
                    fputcsv($f, $headers);
                    foreach ($data as $row) fputcsv($f, $row);
                    fclose($f);
                }, 'export-' . date('Y-m-d') . '.csv', ['Content-Type' => 'text/csv']);
            });
    }

    // ─── Excel ──────────────────────────────────────────────────────────────

    protected static function excelAction(string $name, bool $isBulk)
    {
        if ($isBulk) {
            return BulkAction::make($name)
                ->label('Download Excel')
                ->icon('heroicon-o-table-cells')
                ->accessSelectedRecords()
                ->action(function ($livewire, $records) {
                    $columns = $livewire->getTable()->getColumns();
                    $headers = self::getHeaders($columns);
                    $data = self::formatRecords($records, $columns);
                    $filterStr = self::getActiveFiltersString($livewire);
                    $title = $livewire->getTable()->getHeading() ?? 'Exported Data';
                    return Excel::download(self::makeExcelExport($headers, $data, $filterStr, $title), 'export-' . date('Y-m-d') . '.xlsx');
                });
        }

        return Action::make($name)
            ->label('Download Excel')
            ->icon('heroicon-o-table-cells')
            ->action(function ($livewire) {
                $columns = $livewire->getTable()->getColumns();
                $headers = self::getHeaders($columns);
                $data = self::formatRecords($livewire->getFilteredTableQuery()->get(), $columns);
                $filterStr = self::getActiveFiltersString($livewire);
                $title = $livewire->getTable()->getHeading() ?? 'Exported Data';
                return Excel::download(self::makeExcelExport($headers, $data, $filterStr, $title), 'export-' . date('Y-m-d') . '.xlsx');
            });
    }

    private static function makeExcelExport($headers, $data, $filterStr, $title)
    {
        return new class($headers, $data, $filterStr, $title) implements FromCollection, WithHeadings, WithEvents {
            public function __construct(
                public array $headers,
                public array $data,
                public string $filterStr,
                public ?string $title
            ) {}
            public function collection() { return collect($this->data); }
            public function headings(): array { return $this->headers; }
            public function registerEvents(): array {
                return [BeforeSheet::class => function (BeforeSheet $event) {
                    $event->sheet->insertNewRowBefore(1, 3);
                    $event->sheet->setCellValue('A1', $this->title ?? 'Exported Data');
                    $event->sheet->setCellValue('A2', 'Active Filters: ' . $this->filterStr);
                }];
            }
        };
    }

    // ─── PDF ────────────────────────────────────────────────────────────────

    protected static function pdfAction(string $name, bool $isBulk)
    {
        if ($isBulk) {
            return BulkAction::make($name)
                ->label('Download PDF')
                ->icon('heroicon-o-document')
                ->accessSelectedRecords()
                ->action(function ($livewire, $records) {
                    $columns = $livewire->getTable()->getColumns();
                    $headers = self::getHeaders($columns);
                    $data = self::formatRecords($records, $columns);
                    $filterStr = self::getActiveFiltersString($livewire);
                    $title = $livewire->getTable()->getHeading() ?? 'Exported Data';
                    $pdf = Pdf::loadView('exports.recruitment_print', compact('headers', 'data', 'filterStr', 'title'))->setPaper('a4', 'landscape');
                    return response()->streamDownload(fn () => print($pdf->output()), 'export-' . date('Y-m-d') . '.pdf');
                });
        }

        return Action::make($name)
            ->label('Download PDF')
            ->icon('heroicon-o-document')
            ->action(function ($livewire) {
                $columns = $livewire->getTable()->getColumns();
                $headers = self::getHeaders($columns);
                $data = self::formatRecords($livewire->getFilteredTableQuery()->get(), $columns);
                $filterStr = self::getActiveFiltersString($livewire);
                $title = $livewire->getTable()->getHeading() ?? 'Exported Data';
                $pdf = Pdf::loadView('exports.recruitment_print', compact('headers', 'data', 'filterStr', 'title'))->setPaper('a4', 'landscape');
                return response()->streamDownload(fn () => print($pdf->output()), 'export-' . date('Y-m-d') . '.pdf');
            });
    }

    // ─── Print ──────────────────────────────────────────────────────────────

    protected static function printAction(string $name, bool $isBulk)
    {
        if ($isBulk) {
            return BulkAction::make($name)
                ->label('Print')
                ->icon('heroicon-o-printer')
                ->accessSelectedRecords()
                ->action(function ($livewire, $records) {
                    $columns = $livewire->getTable()->getColumns();
                    $headers = self::getHeaders($columns);
                    $data = self::formatRecords($records, $columns);
                    $key = 'print_' . Str::uuid();
                    Cache::put($key, [
                        'headers' => $headers,
                        'data' => $data,
                        'filterStr' => self::getActiveFiltersString($livewire),
                        'title' => $livewire->getTable()->getHeading() ?? 'Exported Data',
                    ], now()->addMinutes(10));
                    $livewire->redirect(route('recruitment.print', ['key' => $key]));
                });
        }

        return Action::make($name)
            ->label('Print')
            ->icon('heroicon-o-printer')
            ->action(function ($livewire) {
                $columns = $livewire->getTable()->getColumns();
                $headers = self::getHeaders($columns);
                $data = self::formatRecords($livewire->getFilteredTableQuery()->get(), $columns);
                $key = 'print_' . Str::uuid();
                Cache::put($key, [
                    'headers' => $headers,
                    'data' => $data,
                    'filterStr' => self::getActiveFiltersString($livewire),
                    'title' => $livewire->getTable()->getHeading() ?? 'Exported Data',
                ], now()->addMinutes(10));
                $livewire->redirect(route('recruitment.print', ['key' => $key]));
            });
    }
}

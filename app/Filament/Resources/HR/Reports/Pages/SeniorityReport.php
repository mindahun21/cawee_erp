<?php

namespace App\Filament\Resources\HR\Reports\Pages;

use App\Exports\HrReportExport;
use App\Filament\Resources\HR\Reports\HrReportResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class SeniorityReport extends ListRecords
{
    protected static string $resource = HrReportResource::class;

    protected static ?string $title = '📈 Changes in Staff by Seniority';

    public function getSubheading(): string | Htmlable | null
    {
        return view('filament.hr-report-header');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_excel')->label('Export Excel')->icon('heroicon-o-table-cells')->color('success')
                ->action(fn () => Excel::download(new HrReportExport('seniority'), 'seniority_' . now()->format('Ymd') . '.xlsx')),
            Action::make('export_pdf')->label('Export PDF')->icon('heroicon-o-document')->color('danger')
                ->action(function () {
                    $data = (new HrReportExport('seniority'))->collection();
                    $pdf  = Pdf::loadView('exports.hr-report-pdf', ['data' => $data, 'report' => 'seniority'])->setPaper('a4', 'landscape');
                    return response()->streamDownload(fn () => print($pdf->output()), 'seniority_' . now()->format('Ymd') . '.pdf');
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(static::getResource()::getEloquentQuery()->with(['department', 'jobPosition'])->whereNull('date_resigned')->whereNotNull('date_of_employment')->orderBy('date_of_employment'))
            ->columns([
                TextColumn::make('full_name')->label('Employee')->searchable(['first_name', 'last_name'])->weight('semibold'),
                TextColumn::make('department.name')->label('Department')->badge()->color('gray'),
                TextColumn::make('jobPosition.title')->label('Position')->limit(30),
                TextColumn::make('date_of_employment')->label('Start Date')->date()->sortable(),
                TextColumn::make('years')->label('Years')->alignCenter()->fontFamily('mono')->color('primary')->weight('bold')
                    ->getStateUsing(fn ($record) => $record->date_of_employment->diffInYears(now())),
                TextColumn::make('band')->label('Seniority Band')->badge()->color('amber')
                    ->getStateUsing(function ($record) {
                        $years = $record->date_of_employment->diffInYears(now());
                        return match (true) {
                            $years < 1  => 'Probation (< 1yr)',
                            $years < 3  => 'Junior (1-3 yrs)',
                            $years < 7  => 'Mid-level (3-7 yrs)',
                            $years < 15 => 'Senior (7-15 yrs)',
                            default     => 'Veteran (15+ yrs)',
                        };
                    }),
            ])
            ->filters([
                SelectFilter::make('department_id')->label('Department')->relationship('department', 'name'),
            ]);
    }
}

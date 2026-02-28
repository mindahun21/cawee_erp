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

class SalaryReport extends ListRecords
{
    protected static string $resource = HrReportResource::class;

    protected static ?string $title = '💰 Salary Changes Report';

    public function getSubheading(): string | Htmlable | null
    {
        return view('filament.hr-report-header');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_excel')->label('Export Excel')->icon('heroicon-o-table-cells')->color('success')
                ->action(fn () => Excel::download(new HrReportExport('salary_changes'), 'salary_' . now()->format('Ymd') . '.xlsx')),
            Action::make('export_pdf')->label('Export PDF')->icon('heroicon-o-document')->color('danger')
                ->action(function () {
                    $data = (new HrReportExport('salary_changes'))->collection();
                    $pdf  = Pdf::loadView('exports.hr-report-pdf', ['data' => $data, 'report' => 'salary_changes'])->setPaper('a4', 'landscape');
                    return response()->streamDownload(fn () => print($pdf->output()), 'salary_' . now()->format('Ymd') . '.pdf');
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(static::getResource()::getEloquentQuery()->with(['department', 'jobPosition', 'salaryGrade'])->whereNull('date_resigned')->orderByDesc('basic_salary'))
            ->columns([
                TextColumn::make('full_name')->label('Employee')->searchable(['first_name', 'last_name'])->weight('semibold'),
                TextColumn::make('department.name')->label('Department')->badge()->color('info'),
                TextColumn::make('jobPosition.title')->label('Position')->limit(30),
                TextColumn::make('basic_salary')->label('Basic Salary (ETB)')->money('ETB', true)->alignRight()->color('success')->weight('bold')->sortable(),
                TextColumn::make('salaryGrade.grade')->label('Grade / Step')
                    ->getStateUsing(fn ($record) => $record->salaryGrade ? "Grade {$record->salaryGrade->grade} Step {$record->salaryGrade->step}" : '–'),
            ])
            ->filters([
                SelectFilter::make('department_id')->label('Department')->relationship('department', 'name'),
            ]);
    }
}

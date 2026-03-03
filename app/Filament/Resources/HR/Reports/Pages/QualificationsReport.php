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

class QualificationsReport extends ListRecords
{
    protected static string $resource = HrReportResource::class;

    protected static ?string $title = '🎓 Qualifications by Department';

    public function getSubheading(): string | Htmlable | null
    {
        return view('filament.hr-report-header');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_excel')->label('Export Excel')->icon('heroicon-o-table-cells')->color('success')
                ->action(fn () => Excel::download(new HrReportExport('qualifications'), 'qualifications_' . now()->format('Ymd') . '.xlsx')),
            Action::make('export_pdf')->label('Export PDF')->icon('heroicon-o-document')->color('danger')
                ->action(function () {
                    $data = (new HrReportExport('qualifications'))->collection();
                    $pdf  = Pdf::loadView('exports.hr-report-pdf', ['data' => $data, 'report' => 'qualifications'])->setPaper('a4', 'landscape');
                    return response()->streamDownload(fn () => print($pdf->output()), 'qualifications_' . now()->format('Ymd') . '.pdf');
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(static::getResource()::getEloquentQuery()->with(['department', 'educationLevel', 'fieldOfStudy'])->whereNull('date_resigned')->orderBy('first_name'))
            ->columns([
                TextColumn::make('full_name')->label('Employee')->searchable(['first_name', 'last_name'])->weight('semibold'),
                TextColumn::make('department.name')->label('Department')->badge()->color('info'),
                TextColumn::make('education_level_text')->label('Education Level')->badge()->color('purple')
                    ->getStateUsing(fn ($record) => $record->educationLevel?->name ?? ($record->education_level ?? '–')),
                TextColumn::make('field_of_study_text')->label('Field of Study')->limit(40)
                    ->getStateUsing(fn ($record) => $record->fieldOfStudy?->name ?? ($record->field_of_study ?? '–')),
            ])
            ->filters([
                SelectFilter::make('department_id')->label('Department')->relationship('department', 'name'),
                SelectFilter::make('education_level_id')->label('Education Level')->relationship('educationLevel', 'name'),
            ]);
    }
}

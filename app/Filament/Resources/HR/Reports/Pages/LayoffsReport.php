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
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class LayoffsReport extends ListRecords
{
    protected static string $resource = HrReportResource::class;

    protected static ?string $title = '📤 Laying Off Staffs Report';

    public function getSubheading(): string | Htmlable | null
    {
        return view('filament.hr-report-header');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_excel')->label('Export Excel')->icon('heroicon-o-table-cells')->color('success')
                ->action(fn () => Excel::download(new HrReportExport('layoffs'), 'layoffs_' . now()->format('Ymd') . '.xlsx')),
            Action::make('export_pdf')->label('Export PDF')->icon('heroicon-o-document')->color('danger')
                ->action(function () {
                    $data = (new HrReportExport('layoffs'))->collection();
                    $pdf  = Pdf::loadView('exports.hr-report-pdf', ['data' => $data, 'report' => 'layoffs'])->setPaper('a4', 'landscape');
                    return response()->streamDownload(fn () => print($pdf->output()), 'layoffs_' . now()->format('Ymd') . '.pdf');
                }),
        ];
    }

    public function table(Table $table): Table
    {
        // Query only resigned employees
        $years = collect(range(now()->year, now()->subYears(10)->year))->mapWithKeys(fn ($y) => [$y => (string) $y])->toArray();

        return $table
            ->query(static::getResource()::getEloquentQuery()->with(['department', 'jobPosition'])->whereNotNull('date_resigned')->orderByDesc('date_resigned'))
            ->columns([
                TextColumn::make('full_name')->label('Employee')->searchable(['first_name', 'last_name'])->weight('semibold'),
                TextColumn::make('department.name')->label('Department')->badge()->color('info'),
                TextColumn::make('jobPosition.title')->label('Position')->limit(30),
                TextColumn::make('date_resigned')->label('Resigned On')->date()->sortable(),
                TextColumn::make('years_of_service')->label('Years of Service')->alignCenter()->fontFamily('mono')
                    ->getStateUsing(fn ($record) => $record->date_of_employment ? (int) $record->date_of_employment->diffInYears($record->date_resigned ?? now()) : '–'),
            ])
            ->filters([
                SelectFilter::make('department_id')->label('Department')->relationship('department', 'name'),
                SelectFilter::make('year')->label('Resignation Year')->options($years)
                    ->query(fn (Builder $query, array $data) => $data['value'] ? $query->whereYear('date_resigned', $data['value']) : $query),
            ]);
    }
}

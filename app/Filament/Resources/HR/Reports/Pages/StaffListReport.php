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

class StaffListReport extends ListRecords
{
    protected static string $resource = HrReportResource::class;

    protected static ?string $title = '👥 Staff List Report';

    public function getSubheading(): string | Htmlable | null
    {
        return view('filament.hr-report-header');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_excel')->label('Export Excel')->icon('heroicon-o-table-cells')->color('success')
                ->action(fn () => Excel::download(new HrReportExport('staff_list'), 'staff_list_' . now()->format('Ymd') . '.xlsx')),
            Action::make('export_pdf')->label('Export PDF')->icon('heroicon-o-document')->color('danger')
                ->action(function () {
                    $data = (new HrReportExport('staff_list'))->collection();
                    $pdf  = Pdf::loadView('exports.hr-report-pdf', ['data' => $data, 'report' => 'staff_list'])->setPaper('a4', 'landscape');
                    return response()->streamDownload(fn () => print($pdf->output()), 'staff_list_' . now()->format('Ymd') . '.pdf');
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(static::getResource()::getEloquentQuery()->with(['department', 'jobPosition', 'contractType'])->orderBy('first_name'))
            ->columns([
                TextColumn::make('full_name')->label('Employee')->searchable(['first_name', 'last_name'])->weight('semibold'),
                TextColumn::make('department.name')->label('Department')->badge()->color('info'),
                TextColumn::make('jobPosition.title')->label('Position')->limit(20),
                TextColumn::make('contractType.name')->label('Contract')
                    ->getStateUsing(fn ($record) => $record->contractType?->name ?? $record->employment_type ?? '—'),
                TextColumn::make('date_of_employment')->label('Start Date')->date(),
                TextColumn::make('basic_salary')->label('Salary (ETB)')->money('ETB', true)->alignRight(),
                TextColumn::make('date_resigned')->label('Status')->badge()->color(fn ($state) => $state ? 'danger' : 'success')
                    ->formatStateUsing(fn ($state) => $state ? 'Resigned' : 'Active'),
            ])
            ->filters([
                SelectFilter::make('department_id')->label('Department')->relationship('department', 'name'),
                SelectFilter::make('status')->label('Status')->options(['active' => 'Active', 'resigned' => 'Resigned'])
                    ->query(fn (Builder $query, array $data) => match ($data['value']) {
                        'active'   => $query->whereNull('date_resigned'),
                        'resigned' => $query->whereNotNull('date_resigned'),
                        default    => $query,
                    }),
            ]);
    }
}

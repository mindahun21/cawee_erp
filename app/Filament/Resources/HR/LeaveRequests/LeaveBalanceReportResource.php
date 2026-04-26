<?php

namespace App\Filament\Resources\HR\LeaveRequests;

use App\Models\Employee;
use App\Services\HR\LeaveBalanceService;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Traits\BelongsToModule;

/**
 * Leave Balance Report Resource
 *
 * Shows a per-employee annual leave balance summary table.
 * A "View Detail" modal opens the full period-by-period FIFO report.
 */
class LeaveBalanceReportResource extends Resource
{
    use BelongsToModule;
    protected static ?string $model = Employee::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationLabel = 'Leave Balance Report';

    protected static ?string $slug = 'hr/leave-balance-report';

    // ── Table ─────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                Employee::query()
                    ->whereNull('date_resigned')
                    ->whereNotNull('date_of_employment')
                    ->orderBy('first_name')
            )
            ->columns([
                TextColumn::make('full_name')
                    ->label('Employee')
                    ->getStateUsing(fn ($record) => "{$record->first_name} {$record->last_name}")
                    ->searchable(['first_name', 'last_name'])
                    ->sortable('first_name'),

                TextColumn::make('date_of_employment')
                    ->label('Hire Date')
                    ->date('d M Y'),

                TextColumn::make('current_balance')
                    ->label('Available Balance (days)')
                    ->getStateUsing(function ($record) {
                        return (new LeaveBalanceService())->getRemainingBalance($record);
                    })
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state > 10 => 'success',
                        $state > 0  => 'warning',
                        default     => 'danger',
                    })
                    ->sortable(false),
            ])
            ->recordActions([
                Action::make('view_report')
                    ->label('View Detail')
                    ->icon('heroicon-o-document-chart-bar')
                    ->color('info')
                    ->modalHeading(fn ($record) => "{$record->first_name} {$record->last_name} — Leave Balance Report")
                    ->modalWidth('5xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalContent(function ($record) {
                        $svc    = new LeaveBalanceService();
                        $report = $svc->getBalanceReport($record);
                        return view('filament.leave.balance-report-table', compact('report', 'record'));
                    }),
            ])
            ->filters([]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\HR\LeaveRequests\LeaveBalanceReportResource\Pages\ListLeaveBalanceReports::route('/'),
        ];
    }
}

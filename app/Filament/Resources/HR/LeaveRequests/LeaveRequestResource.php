<?php

namespace App\Filament\Resources\HR\LeaveRequests;

use App\Models\Employee;
use App\Models\HrLeaveRequest;
use App\Models\HrLeaveType;
use App\Services\HR\LeaveBalanceService;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;
use App\Filament\Resources\HR\LeaveRequests\LeaveRequestResource\Pages;
use App\Traits\BelongsToModule;

class LeaveRequestResource extends Resource
{
    use BelongsToModule;
    protected static ?string $model = HrLeaveRequest::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static string|UnitEnum|null $navigationGroup = 'Human Resources';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Leave Requests';

    // ── Form ─────────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([

            // ── Employee & Leave Type ───────────────────────────────────
            Section::make('Leave Request Details')
                ->columns(2)
                ->components([
                    Select::make('employee_id')
                        ->label('Employee')
                        ->relationship('employee', 'first_name')
                        ->getOptionLabelFromRecordUsing(
                            fn($record) => "{$record->first_name} {$record->last_name}"
                        )
                        ->searchable(['first_name', 'last_name'])
                        ->preload()
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (Set $set) {
                            $set('hr_leave_type_id', null);
                            $set('start_date', null);
                            $set('end_date', null);
                            $set('no_of_days', null);
                        }),

                    Select::make('hr_leave_type_id')
                        ->label('Leave Type')
                        ->options(fn() => HrLeaveType::where('is_active', true)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray()
                        )
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, Set $set) {
                            if (! $state) return;
                            $type = HrLeaveType::find($state);
                            if ($type && $type->default_days > 0) {
                                $set('no_of_days', $type->default_days);
                            }
                        }),

                    // Balance hint — only shows for annual leave type
                    Placeholder::make('balance_info')
                        ->label('Annual Leave Balance')
                        ->content(function (Get $get) {
                            $employeeId  = $get('employee_id');
                            $leaveTypeId = $get('hr_leave_type_id');
                            if (! $employeeId || ! $leaveTypeId) return '—';

                            $type = HrLeaveType::find($leaveTypeId);
                            if (! $type || ! $type->is_annual) return '(not annual leave)';

                            $employee = Employee::find($employeeId);
                            if (! $employee) return '—';

                            $available = (new LeaveBalanceService())->getRemainingBalance($employee);
                            return "{$available} day(s) available";
                        })
                        ->columnSpan(2),
                ]),

            // ── Dates & Duration ─────────────────────────────────────────
            Section::make('Dates & Duration')
                ->columns(2)
                ->components([
                    DatePicker::make('start_date')
                        ->label('Start Date')
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, Get $get, Set $set) {
                            static::recalculateEndDate($state, $get, $set);
                        })
                        ->hidden(fn(Get $get) => static::isHourly($get)),

                    TextInput::make('no_of_days')
                        ->label('Number of Days')
                        ->numeric()
                        ->minValue(1)
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, Get $get, Set $set) {
                            static::recalculateEndDate($get('start_date'), $get, $set);
                        })
                        ->hidden(fn(Get $get) => static::isHourly($get)),

                    DatePicker::make('end_date')
                        ->label('End Date (auto-calculated)')
                        ->disabled()
                        ->dehydrated(true)
                        ->hidden(fn(Get $get) => static::isHourly($get)),

                    // Hourly fields
                    DatePicker::make('start_date')  // reuse for hourly — same field, different visibility
                        ->label('Date (Hourly Leave)')
                        ->required()
                        ->live()
                        ->hidden(fn(Get $get) => ! static::isHourly($get)),

                    TimePicker::make('from_time')
                        ->label('From Time')
                        ->seconds(false)
                        ->hidden(fn(Get $get) => ! static::isHourly($get)),

                    TimePicker::make('to_time')
                        ->label('To Time')
                        ->seconds(false)
                        ->hidden(fn(Get $get) => ! static::isHourly($get)),

                    TextInput::make('total_hours')
                        ->label('Total Hours')
                        ->numeric()
                        ->disabled()
                        ->dehydrated(true)
                        ->hidden(fn(Get $get) => ! static::isHourly($get)),
                ]),

            // ── Reason & Document ────────────────────────────────────────
            Section::make('Reason & Documentation')
                ->components([
                    Textarea::make('reason')
                        ->label('Reason for Leave')
                        ->required()
                        ->rows(3)
                        ->columnSpanFull(),

                    FileUpload::make('supporting_document')
                        ->label('Supporting Document (PDF or image)')
                        ->directory('leave-docs')
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                        ->hidden(fn(Get $get) => ! static::requiresDocument($get)),
                ]),
        ]);
    }

    // ── Table ─────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.first_name')
                    ->label('Employee')
                    ->formatStateUsing(fn($record) => "{$record->employee->first_name} {$record->employee->last_name}")
                    ->searchable()
                    ->sortable(),

                TextColumn::make('leaveType.name')
                    ->label('Leave Type')
                    ->badge()
                    ->sortable(),

                TextColumn::make('start_date')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('end_date')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('no_of_days')
                    ->label('Days')
                    ->alignCenter(),

                TextColumn::make('approval_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'Approved' => 'success',
                        'Rejected' => 'danger',
                        default    => 'warning',
                    }),

                TextColumn::make('current_stage')
                    ->label('Stage')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'Fully Approved'      => 'success',
                        'Rejected'            => 'danger',
                        'Awaiting Supervisor' => 'warning',
                        'Awaiting HR'         => 'info',
                        'Awaiting Director'   => 'primary',
                        default               => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label('Submitted')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('approval_status')
                    ->label('Status')
                    ->options([
                        'Pending'  => 'Pending',
                        'Approved' => 'Approved',
                        'Rejected' => 'Rejected',
                    ]),
                SelectFilter::make('hr_leave_type_id')
                    ->label('Leave Type')
                    ->relationship('leaveType', 'name'),
            ])
            ->recordActions([
                // Stage 1 — Supervisor
                Action::make('supervisor_approve')
                    ->label('Supervisor ✓')
                    ->icon('heroicon-o-hand-thumb-up')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve as Supervisor?')
                    ->visible(fn(HrLeaveRequest $record) => $record->canSupervisorApprove())
                    ->action(function (HrLeaveRequest $record) {
                        $record->update([
                            'supervisor_status'      => HrLeaveRequest::STATUS_APPROVED,
                            'supervisor_approved_at' => now(),
                        ]);
                        Notification::make()->success()->title('Supervisor approved')->send();
                    }),

                // Stage 2 — HR
                Action::make('hr_approve')
                    ->label('HR ✓')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve as HR?')
                    ->visible(fn(HrLeaveRequest $record) => $record->canHrApprove())
                    ->action(function (HrLeaveRequest $record) {
                        $record->update([
                            'hr_status'      => HrLeaveRequest::STATUS_APPROVED,
                            'hr_approved_at' => now(),
                        ]);
                        Notification::make()->success()->title('HR approved')->send();
                    }),

                // Stage 3 — Director (final)
                Action::make('director_approve')
                    ->label('Director ✓')
                    ->icon('heroicon-o-star')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Fully approve this leave request?')
                    ->visible(fn(HrLeaveRequest $record) => $record->canDirectorApprove())
                    ->action(function (HrLeaveRequest $record) {
                        $record->update([
                            'director_status'      => HrLeaveRequest::STATUS_APPROVED,
                            'director_approved_at' => now(),
                            'approval_status'      => HrLeaveRequest::STATUS_APPROVED,
                            'approval_date'        => today(),
                        ]);
                        Notification::make()->success()->title('Leave fully approved!')->send();
                    }),

                // Reject — any pending stage
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Reject this leave request?')
                    ->visible(fn(HrLeaveRequest $record) => $record->approval_status === HrLeaveRequest::STATUS_PENDING)
                    ->action(function (HrLeaveRequest $record) {
                        $record->update([
                            'approval_status' => HrLeaveRequest::STATUS_REJECTED,
                        ]);
                        Notification::make()->danger()->title('Leave request rejected')->send();
                    }),

                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListLeaveRequests::route('/'),
            'create' => Pages\CreateLeaveRequest::route('/create'),
            'edit'   => Pages\EditLeaveRequest::route('/{record}/edit'),
        ];
    }

    // ── Private Helpers ────────────────────────────────────────────────────

    private static function recalculateEndDate(?string $startDate, Get $get, Set $set): void
    {
        $leaveTypeId = $get('hr_leave_type_id');
        $noOfDays    = (int) $get('no_of_days');

        if (! $startDate || ! $leaveTypeId || $noOfDays <= 0) return;

        $type = HrLeaveType::find($leaveTypeId);
        if (! $type || $type->is_hourly) return;

        $start   = Carbon::parse($startDate);
        $endDate = (new LeaveBalanceService())->computeEndDate($start, $noOfDays, $type->is_working_days);

        $set('end_date', $endDate->toDateString());
    }

    private static function isHourly(Get $get): bool
    {
        $typeId = $get('hr_leave_type_id');
        if (! $typeId) return false;
        $type = HrLeaveType::find($typeId);
        return $type && $type->is_hourly;
    }

    private static function requiresDocument(Get $get): bool
    {
        $typeId = $get('hr_leave_type_id');
        if (! $typeId) return false;
        $type = HrLeaveType::find($typeId);
        return $type && $type->requires_document;
    }
}

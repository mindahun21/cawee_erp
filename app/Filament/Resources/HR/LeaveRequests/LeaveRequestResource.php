<?php

namespace App\Filament\Resources\HR\LeaveRequests;

use App\Filament\Resources\HR\LeaveRequests\Pages\CreateLeaveRequest;
use App\Filament\Resources\HR\LeaveRequests\Pages\EditLeaveRequest;
use App\Filament\Resources\HR\LeaveRequests\Pages\ListLeaveRequests;
use App\Models\Employee;
use App\Models\LeaveRequest;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LeaveRequestResource extends Resource
{
    protected static ?string $model = LeaveRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|\UnitEnum|null $navigationGroup = 'Human Resources';

    protected static ?string $navigationLabel = 'Leave Requests';

    protected static ?int $navigationSort = 4;

    // ── Form ──────────────────────────────────────────────────────
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Leave Details')->columns(2)->schema([
                Select::make('employee_id')
                    ->label('Employee')
                    ->relationship('employee', 'first_name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                    ->searchable()
                    ->required(),

                Select::make('leave_type')
                    ->options([
                        'Annual'    => 'Annual',
                        'Sick'      => 'Sick',
                        'Maternity' => 'Maternity',
                        'Field'     => 'Field',
                        'Unpaid'    => 'Unpaid',
                        'Other'     => 'Other',
                    ])
                    ->required(),

                DatePicker::make('start_date')->required(),
                DatePicker::make('end_date')->required()->afterOrEqual('start_date'),

                Textarea::make('remarks')->rows(3)->columnSpanFull(),

                FileUpload::make('supporting_document')
                    ->label('Supporting Document (e.g. medical note)')
                    ->disk('local')
                    ->directory('leave-docs')
                    ->nullable()
                    ->columnSpanFull(),
            ]),

            Section::make('Approval Status')
                ->description('Approvals are performed via the ⚡ action buttons on each record — not manually set here.')
                ->columns(3)
                ->schema([
                    Select::make('supervisor_status')
                        ->options(['Pending' => 'Pending', 'Approved' => 'Approved', 'Rejected' => 'Rejected'])
                        ->default('Pending')
                        ->disabled()
                        ->dehydrated()
                        ->label('Supervisor'),

                    Select::make('hr_status')
                        ->options(['Pending' => 'Pending', 'Approved' => 'Approved', 'Rejected' => 'Rejected'])
                        ->default('Pending')
                        ->disabled()
                        ->dehydrated()
                        ->label('HR'),

                    Select::make('approval_status')
                        ->options(['Pending' => 'Pending', 'Approved' => 'Approved', 'Rejected' => 'Rejected'])
                        ->default('Pending')
                        ->disabled()
                        ->dehydrated()
                        ->label('Director / Final'),
                ])
                ->collapsible(),
        ]);
    }

    // ── Table ─────────────────────────────────────────────────────
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.full_name')
                    ->label('Employee')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable()
                    ->weight('semibold'),

                TextColumn::make('leave_type')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Annual'    => 'info',
                        'Sick'      => 'danger',
                        'Maternity' => 'pink',
                        'Field'     => 'success',
                        'Unpaid'    => 'gray',
                        default     => 'gray',
                    }),

                TextColumn::make('start_date')->date()->sortable(),
                TextColumn::make('end_date')->date()->sortable(),

                TextColumn::make('duration_in_days')
                    ->label('Days')
                    ->badge()
                    ->color('gray'),

                // Stage-by-stage indicator columns
                TextColumn::make('supervisor_status')
                    ->label('Supervisor')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Approved' => 'success',
                        'Rejected' => 'danger',
                        default    => 'warning',
                    }),

                TextColumn::make('hr_status')
                    ->label('HR')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Approved' => 'success',
                        'Rejected' => 'danger',
                        default    => 'warning',
                    }),

                TextColumn::make('approval_status')
                    ->label('Director')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Approved' => 'success',
                        'Rejected' => 'danger',
                        default    => 'warning',
                    }),

                TextColumn::make('current_stage')
                    ->label('Stage')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        str_contains($state, 'Approved')  => 'success',
                        str_contains($state, 'Rejected')  => 'danger',
                        str_contains($state, 'Awaiting')  => 'warning',
                        default                           => 'gray',
                    }),
            ])
            ->defaultSort('start_date', 'desc')
            ->filters([
                SelectFilter::make('supervisor_status')
                    ->label('Supervisor Status')
                    ->options(['Pending' => 'Pending', 'Approved' => 'Approved', 'Rejected' => 'Rejected']),

                SelectFilter::make('hr_status')
                    ->label('HR Status')
                    ->options(['Pending' => 'Pending', 'Approved' => 'Approved', 'Rejected' => 'Rejected']),

                SelectFilter::make('approval_status')
                    ->label('Director Status')
                    ->options(['Pending' => 'Pending', 'Approved' => 'Approved', 'Rejected' => 'Rejected']),

                SelectFilter::make('leave_type')
                    ->options([
                        'Annual' => 'Annual', 'Sick' => 'Sick', 'Maternity' => 'Maternity',
                        'Field'  => 'Field',  'Unpaid' => 'Unpaid', 'Other' => 'Other',
                    ]),
            ])
            ->recordActions([
                // ── Supervisor Approve ──────────────────────────
                Action::make('supervisor_approve')
                    ->label('Supervisor Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (LeaveRequest $record) =>
                        $record->canSupervisorApprove() && auth()->user()->isHrSupervisor()
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Approve as Supervisor')
                    ->modalDescription('Confirm that you are approving this leave request at the supervisor level. This will forward the request to HR for review.')
                    ->modalSubmitActionLabel('Approve')
                    ->action(fn (LeaveRequest $record) => $record->update([
                        'supervisor_status'      => 'Approved',
                        'supervisor_approved_at' => now(),
                    ]) && Notification::make()->title('Approved at Supervisor level — forwarded to HR')->success()->send()),

                // ── HR Approve ──────────────────────────────────
                Action::make('hr_approve')
                    ->label('HR Approve')
                    ->icon('heroicon-o-check-badge')
                    ->color('info')
                    ->visible(fn (LeaveRequest $record) =>
                        $record->canHrApprove() && auth()->user()->isHrOfficer()
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Approve as HR')
                    ->modalDescription('Confirm HR approval. This will forward the request to the Director for final authorization.')
                    ->modalSubmitActionLabel('Approve')
                    ->action(fn (LeaveRequest $record) => $record->update([
                        'hr_status'      => 'Approved',
                        'hr_approved_at' => now(),
                    ]) && Notification::make()->title('Approved at HR level — forwarded to Director')->success()->send()),

                // ── Director Approve (Final) ─────────────────────
                Action::make('director_approve')
                    ->label('Authorize (Director)')
                    ->icon('heroicon-o-shield-check')
                    ->color('primary')
                    ->visible(fn (LeaveRequest $record) =>
                        $record->canDirectorApprove() && auth()->user()->isHrDirector()
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Final Authorization — Director')
                    ->modalDescription('By clicking Authorize, this leave request will be fully approved.')
                    ->modalSubmitActionLabel('Authorize')
                    ->action(fn (LeaveRequest $record) => $record->update([
                        'approval_status'      => 'Approved',
                        'director_approved_at' => now(),
                        'approval_date'        => now()->toDateString(),
                    ]) && Notification::make()->title('Leave request fully authorized ✓')->success()->send()),

                // ── Reject (any stage) ──────────────────────────
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (LeaveRequest $record) =>
                        ! $record->isRejected()
                        && ! $record->isFullyApproved()
                        && auth()->user()->isHrSupervisor()
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Reject Leave Request')
                    ->modalDescription('This will mark the leave request as Rejected. Please confirm.')
                    ->modalSubmitActionLabel('Reject')
                    ->action(function (LeaveRequest $record) {
                        // Reject at whichever stage it's currently at
                        if ($record->supervisor_status === 'Pending') {
                            $record->update(['supervisor_status' => 'Rejected', 'supervisor_approved_at' => now()]);
                        } else {
                            $record->update(['hr_status' => 'Rejected', 'hr_approved_at' => now()]);
                        }
                        $record->update(['approval_status' => 'Rejected']);
                        Notification::make()->title('Leave request rejected')->danger()->send();
                    }),

                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListLeaveRequests::route('/'),
            'create' => CreateLeaveRequest::route('/create'),
            'edit'   => EditLeaveRequest::route('/{record}/edit'),
        ];
    }
}

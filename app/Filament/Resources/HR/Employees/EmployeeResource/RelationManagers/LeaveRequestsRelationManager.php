<?php

namespace App\Filament\Resources\HR\Employees\EmployeeResource\RelationManagers;

use App\Models\LeaveRequest;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LeaveRequestsRelationManager extends RelationManager
{
    protected static string $relationship = 'leaveRequests';

    protected static ?string $title = 'Leave Requests';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
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
                ->label('Supporting Document')
                ->disk('local')
                ->directory('leave-docs')
                ->nullable()
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('leave_type')->badge()
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

                TextColumn::make('duration_in_days')->label('Days')->badge()->color('gray'),

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
                        str_contains((string) $state, 'Approved') => 'success',
                        str_contains((string) $state, 'Rejected') => 'danger',
                        default                                   => 'warning',
                    }),
            ])
            ->filters([
                SelectFilter::make('supervisor_status')
                    ->label('Supervisor')
                    ->options(['Pending' => 'Pending', 'Approved' => 'Approved', 'Rejected' => 'Rejected']),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([
                Action::make('supervisor_approve')
                    ->label('Supervisor ✓')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (LeaveRequest $record) =>
                        $record->canSupervisorApprove() && auth()->user()->isHrSupervisor()
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Approve at Supervisor Level')
                    ->modalSubmitActionLabel('Approve')
                    ->action(fn (LeaveRequest $record) => $record->update([
                        'supervisor_status'      => 'Approved',
                        'supervisor_approved_at' => now(),
                    ]) && Notification::make()->title('Forwarded to HR')->success()->send()),

                Action::make('hr_approve')
                    ->label('HR ✓')
                    ->icon('heroicon-o-check-badge')
                    ->color('info')
                    ->visible(fn (LeaveRequest $record) =>
                        $record->canHrApprove() && auth()->user()->isHrOfficer()
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Approve at HR Level')
                    ->modalSubmitActionLabel('Approve')
                    ->action(fn (LeaveRequest $record) => $record->update([
                        'hr_status'      => 'Approved',
                        'hr_approved_at' => now(),
                    ]) && Notification::make()->title('Forwarded to Director')->success()->send()),

                Action::make('director_approve')
                    ->label('Authorize ✓')
                    ->icon('heroicon-o-shield-check')
                    ->color('primary')
                    ->visible(fn (LeaveRequest $record) =>
                        $record->canDirectorApprove() && auth()->user()->isHrDirector()
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Final Authorization')
                    ->modalSubmitActionLabel('Authorize')
                    ->action(fn (LeaveRequest $record) => $record->update([
                        'approval_status'      => 'Approved',
                        'director_approved_at' => now(),
                        'approval_date'        => now()->toDateString(),
                    ]) && Notification::make()->title('Leave fully authorized ✓')->success()->send()),

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
                    ->modalSubmitActionLabel('Reject')
                    ->action(function (LeaveRequest $record) {
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
            ->defaultSort('start_date', 'desc');
    }
}

<?php

namespace App\Filament\Resources\HR\Movements;

use App\Filament\Resources\HR\Movements\Pages\CreateEmployeeMovement;
use App\Filament\Resources\HR\Movements\Pages\EditEmployeeMovement;
use App\Filament\Resources\HR\Movements\Pages\ListEmployeeMovements;
use App\Models\EmployeeMovement;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use App\Traits\BelongsToModule;

class EmployeeMovementResource extends Resource
{
    use BelongsToModule;
    protected static ?string $model = EmployeeMovement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static string|\UnitEnum|null $navigationGroup = 'Human Resources';

    protected static ?string $navigationLabel = 'Promotions & Movements';

    protected static ?int $navigationSort = 12;

    protected static ?string $modelLabel = 'Movement';
    protected static ?string $pluralModelLabel = 'Employee Movements';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Movement Details')->columns(2)->schema([
                Select::make('employee_id')
                    ->label('Employee')
                    ->relationship('employee', 'first_name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                    ->searchable()
                    ->preload()
                    ->required()
                    ->columnSpanFull(),

                Select::make('movement_type')
                    ->label('Movement Type')
                    ->options([
                        'Promotion'    => 'Promotion',
                        'Demotion'     => 'Demotion',
                        'Transfer'     => 'Transfer',
                        'Grade Change' => 'Grade Change',
                        'Title Change' => 'Title Change',
                    ])
                    ->required(),

                DatePicker::make('effective_date')->required(),

                TextInput::make('reference_number')->label('Reference No.')->maxLength(50),
            ]),

            Section::make('From → To Changes')->columns(2)->schema([
                Select::make('from_department_id')
                    ->label('From Department')
                    ->relationship('fromDepartment', 'name')
                    ->searchable()->preload()->nullable(),

                Select::make('to_department_id')
                    ->label('To Department')
                    ->relationship('toDepartment', 'name')
                    ->searchable()->preload()->nullable(),

                Select::make('from_job_position_id')
                    ->label('From Position')
                    ->relationship('fromPosition', 'title')
                    ->searchable()->preload()->nullable(),

                Select::make('to_job_position_id')
                    ->label('To Position')
                    ->relationship('toPosition', 'title')
                    ->searchable()->preload()->nullable(),

                TextInput::make('from_salary')->label('From Salary (ETB)')->numeric()->nullable(),
                TextInput::make('to_salary')->label('To Salary (ETB)')->numeric()->nullable(),

                Select::make('from_salary_grade_id')
                    ->label('From Salary Grade')
                    ->relationship('fromGrade', 'grade')
                    ->getOptionLabelFromRecordUsing(fn ($r) => $r ? "Grade {$r->grade} — Step {$r->step} (ETB " . number_format($r->basic_salary) . ")" : '–')
                    ->searchable()->preload()->nullable(),

                Select::make('to_salary_grade_id')
                    ->label('To Salary Grade')
                    ->relationship('toGrade', 'grade')
                    ->getOptionLabelFromRecordUsing(fn ($r) => $r ? "Grade {$r->grade} — Step {$r->step} (ETB " . number_format($r->basic_salary) . ")" : '–')
                    ->searchable()->preload()->nullable(),
            ]),

            Section::make('Notes & Attachment')->columns(2)->schema([
                Textarea::make('reason')->label('Reason / Justification')->columnSpanFull()->rows(3),

                FileUpload::make('attachment_path')
                    ->label('Supporting Document')
                    ->directory('hr/movements')
                    ->columnSpanFull()
                    ->nullable(),
            ]),

            Section::make('Approval Status')
                ->collapsible()
                ->schema([
                    Select::make('status')
                        ->options([
                            'Draft'            => 'Draft',
                            'Pending Approval' => 'Pending Approval',
                            'Approved'         => 'Approved',
                            'Rejected'         => 'Rejected',
                        ])
                        ->default('Pending Approval')
                        ->disabled()
                        ->dehydrated(),

                    Select::make('approved_by')
                        ->label('Approved By')
                        ->relationship('approver', 'name')
                        ->searchable()->preload()->nullable()
                        ->disabled()
                        ->dehydrated(),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.first_name')
                    ->label('Employee')
                    ->getStateUsing(fn ($record) => $record->employee?->full_name)
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                TextColumn::make('movement_type')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'Promotion'    => 'success',
                        'Demotion'     => 'danger',
                        'Transfer'     => 'info',
                        'Grade Change' => 'warning',
                        'Title Change' => 'gray',
                        default        => 'gray',
                    }),

                TextColumn::make('fromDepartment.name')->label('From Dept')->limit(18)->placeholder('–'),
                TextColumn::make('toDepartment.name')->label('To Dept')->limit(18)->placeholder('–'),
                TextColumn::make('fromPosition.title')->label('From Position')->limit(22)->placeholder('–'),
                TextColumn::make('toPosition.title')->label('To Position')->limit(22)->placeholder('–'),

                TextColumn::make('from_salary')->label('From')->money('ETB', true)->placeholder('–'),
                TextColumn::make('to_salary')->label('To')->money('ETB', true)->placeholder('–'),

                TextColumn::make('effective_date')->date()->sortable(),

                TextColumn::make('status')->badge()->color(fn ($state) => match ($state) {
                    'Approved'         => 'success',
                    'Pending Approval' => 'warning',
                    'Draft'            => 'gray',
                    'Rejected'         => 'danger',
                    default            => 'gray',
                }),
            ])
            ->defaultSort('effective_date', 'desc')
            ->filters([
                SelectFilter::make('movement_type')->options([
                    'Promotion' => 'Promotion', 'Demotion' => 'Demotion',
                    'Transfer'  => 'Transfer',  'Grade Change' => 'Grade Change',
                    'Title Change' => 'Title Change',
                ]),
                SelectFilter::make('status')->options([
                    'Draft' => 'Draft', 'Pending Approval' => 'Pending Approval',
                    'Approved' => 'Approved', 'Rejected' => 'Rejected',
                ]),
            ])
            ->recordActions([
                // ── HR Approve ─────────────────────────────────────────
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (EmployeeMovement $record) =>
                        $record->isPending()
                        && (auth()->user()->isHrDirector() || auth()->user()->isHrOfficer() || auth()->user()->isSuperAdmin())
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Approve Movement Record')
                    ->modalDescription('This will mark the movement as Approved and apply it to the employee\'s record. Continue?')
                    ->modalSubmitActionLabel('Approve')
                    ->action(function (EmployeeMovement $record) {
                        $record->update([
                            'status'      => 'Approved',
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                        ]);

                        // Apply changes to the employee record
                        $updates = [];
                        if ($record->to_department_id)    $updates['department_id']    = $record->to_department_id;
                        if ($record->to_job_position_id)  $updates['job_position_id']  = $record->to_job_position_id;
                        if ($record->to_salary)           $updates['basic_salary']      = $record->to_salary;
                        if ($record->to_salary_grade_id)  $updates['salary_grade_id']  = $record->to_salary_grade_id;
                        if (!empty($updates)) {
                            $record->employee->update($updates);
                        }

                        Notification::make()
                            ->title('Movement Approved ✓')
                            ->body('Employee record has been updated accordingly.')
                            ->success()
                            ->send();
                    }),

                // ── Reject ─────────────────────────────────────────────
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (EmployeeMovement $record) =>
                        ! $record->isRejected() && ! $record->isApproved()
                        && (auth()->user()->isHrDirector() || auth()->user()->isSuperAdmin())
                    )
                    ->form([
                        Textarea::make('rejection_reason')->label('Reason for Rejection')->required()->rows(3),
                    ])
                    ->requiresConfirmation()
                    ->modalHeading('Reject Movement')
                    ->modalSubmitActionLabel('Reject')
                    ->action(function (EmployeeMovement $record, array $data) {
                        $record->update([
                            'status'           => 'Rejected',
                            'rejected_at'      => now(),
                            'rejection_reason' => $data['rejection_reason'],
                        ]);
                        Notification::make()->title('Movement Rejected')->danger()->send();
                    }),

                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListEmployeeMovements::route('/'),
            'create' => CreateEmployeeMovement::route('/create'),
            'edit'   => EditEmployeeMovement::route('/{record}/edit'),
        ];
    }
}

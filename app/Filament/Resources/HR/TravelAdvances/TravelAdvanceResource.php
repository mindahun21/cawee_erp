<?php

namespace App\Filament\Resources\HR\TravelAdvances;

use App\Filament\Resources\HR\TravelAdvances\Pages\CreateTravelAdvance;
use App\Filament\Resources\HR\TravelAdvances\Pages\EditTravelAdvance;
use App\Filament\Resources\HR\TravelAdvances\Pages\ListTravelAdvances;
use App\Models\TravelAdvance;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
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

class TravelAdvanceResource extends Resource
{
    use BelongsToModule;
    protected static ?string $model = TravelAdvance::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|\UnitEnum|null $navigationGroup = 'Human Resources';

    protected static ?string $navigationLabel = 'Travel Advances (TARF)';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Travel Request Details')->columns(2)->schema([
                Select::make('employee_id')
                    ->label('Employee')
                    ->relationship('employee', 'first_name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                    ->searchable()->required(),

                Select::make('project_id')
                    ->label('Project')
                    ->relationship('project', 'project_name')
                    ->searchable()->preload()->nullable(),

                TextInput::make('payment_center')->maxLength(150),
                TextInput::make('place_of_travel')->required()->maxLength(150),

                Textarea::make('purpose')->rows(3)->columnSpanFull(),

                DatePicker::make('depart_date')->required(),
                DatePicker::make('return_date')->required()->afterOrEqual('depart_date'),

                TextInput::make('planned_days')->numeric()->minValue(1),
                TextInput::make('per_diem_rate')->numeric()->prefix('ETB')->label('Per Diem Rate/Day'),
            ]),

            Section::make('Budget Breakdown (ETB)')->columns(3)->schema([
                TextInput::make('budget_code')->maxLength(50),
                TextInput::make('budget_title')->maxLength(150),
                TextInput::make('other_description')->maxLength(255)->label('Other (Description)'),

                TextInput::make('per_diem_amount')->numeric()->prefix('ETB')->label('Per Diem'),
                TextInput::make('accommodation_amount')->numeric()->prefix('ETB')->label('Accommodation'),
                TextInput::make('transport_amount')->numeric()->prefix('ETB')->label('Transport'),
                TextInput::make('other_amount')->numeric()->prefix('ETB')->label('Other Amount'),
            ]),

            Section::make('Approval Trail')
                ->description('Approvals are performed with the action buttons on the list view.')
                ->columns(3)
                ->schema([
                    Select::make('status')
                        ->options(['Draft' => 'Draft', 'Submitted' => 'Submitted', 'Approved' => 'Approved', 'Settled' => 'Settled', 'Rejected' => 'Rejected'])
                        ->default('Draft')->disabled()->dehydrated(),
                ])
                ->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.full_name')
                    ->label('Employee')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable()->weight('semibold'),

                TextColumn::make('place_of_travel')->searchable(),
                TextColumn::make('project.project_name')->label('Project')->toggleable(),

                TextColumn::make('depart_date')->date()->sortable(),
                TextColumn::make('return_date')->date()->sortable(),
                TextColumn::make('planned_days')->label('Days'),

                TextColumn::make('total_amount')
                    ->label('Total (ETB)')
                    ->numeric(2)
                    ->prefix('ETB '),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Approved'  => 'success',
                        'Settled'   => 'info',
                        'Rejected'  => 'danger',
                        'Submitted' => 'warning',
                        default     => 'gray',
                    }),
            ])
            ->defaultSort('depart_date', 'desc')
            ->filters([
                SelectFilter::make('status')->options([
                    'Draft' => 'Draft', 'Submitted' => 'Submitted',
                    'Approved' => 'Approved', 'Settled' => 'Settled', 'Rejected' => 'Rejected',
                ]),
            ])
            ->recordActions([
                // Submit for review
                Action::make('submit')
                    ->label('Submit')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->visible(fn (TravelAdvance $r) => $r->status === 'Draft')
                    ->requiresConfirmation()
                    ->modalHeading('Submit Travel Advance Request')
                    ->modalDescription('Submit this request for review and approval.')
                    ->action(fn (TravelAdvance $r) => $r->update(['status' => 'Submitted'])
                        && Notification::make()->title('Request submitted for approval')->info()->send()),

                // Accountant approval
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (TravelAdvance $r) => $r->status === 'Submitted')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Travel Advance')
                    ->modalDescription('Approve and authorize the travel advance payment.')
                    ->action(fn (TravelAdvance $r) => $r->update([
                        'status'      => 'Approved',
                        'approved_at' => now(),
                    ]) && Notification::make()->title('Travel advance approved ✓')->success()->send()),

                // Mark settled (after return)
                Action::make('settle')
                    ->label('Mark Settled')
                    ->icon('heroicon-o-banknotes')
                    ->color('primary')
                    ->visible(fn (TravelAdvance $r) => $r->status === 'Approved')
                    ->requiresConfirmation()
                    ->modalHeading('Mark as Settled')
                    ->modalDescription('Confirm that the travel clearance (TACF) has been completed and the advance is settled.')
                    ->action(fn (TravelAdvance $r) => $r->update(['status' => 'Settled'])
                        && Notification::make()->title('Travel advance marked as settled')->success()->send()),

                // Reject
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (TravelAdvance $r) => in_array($r->status, ['Submitted', 'Draft']))
                    ->requiresConfirmation()
                    ->action(fn (TravelAdvance $r) => $r->update(['status' => 'Rejected'])
                        && Notification::make()->title('Request rejected')->danger()->send()),

                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListTravelAdvances::route('/'),
            'create' => CreateTravelAdvance::route('/create'),
            'edit'   => EditTravelAdvance::route('/{record}/edit'),
        ];
    }
}

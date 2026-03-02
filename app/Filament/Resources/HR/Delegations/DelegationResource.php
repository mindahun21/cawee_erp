<?php

namespace App\Filament\Resources\HR\Delegations;

use App\Filament\Resources\HR\Delegations\Pages\CreateDelegation;
use App\Filament\Resources\HR\Delegations\Pages\EditDelegation;
use App\Filament\Resources\HR\Delegations\Pages\ListDelegations;
use App\Models\Delegation;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
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

class DelegationResource extends Resource
{
    protected static ?string $model = Delegation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|\UnitEnum|null $navigationGroup = 'Human Resources';

    protected static ?string $navigationLabel = 'Delegations';

    protected static ?int $navigationSort = 13;

    protected static ?string $modelLabel = 'Delegation';
    protected static ?string $pluralModelLabel = 'Duty Delegations';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Delegation Details')->columns(2)->schema([
                Select::make('delegator_id')
                    ->label('Delegating Employee (Going Away)')
                    ->relationship('delegator', 'first_name', fn ($query) => $query->select('id', 'first_name', 'last_name'))
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->first_name . ' ' . $record->last_name)
                    ->searchable()
                    ->preload()
                    ->required(),

                Select::make('delegate_id')
                    ->label('Acting Employee (Covering)')
                    ->relationship('delegate', 'first_name', fn ($query) => $query->select('id', 'first_name', 'last_name'))
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->first_name . ' ' . $record->last_name)
                    ->searchable()
                    ->preload()
                    ->required(),

                TextInput::make('subject')
                    ->label('Subject / Title')
                    ->required()
                    ->maxLength(200)
                    ->columnSpanFull(),

                DatePicker::make('start_date')->required(),
                DatePicker::make('end_date')->nullable()->after('start_date'),

                TextInput::make('reason')
                    ->label('Reason (Leave / Travel / Other)')
                    ->maxLength(200),

                Select::make('status')
                    ->options([
                        'Active'    => 'Active',
                        'Completed' => 'Completed',
                        'Cancelled' => 'Cancelled',
                    ])
                    ->default('Active')
                    ->required(),

                TextInput::make('reference_number')->label('Reference No.')->maxLength(50),

                Textarea::make('scope')
                    ->label('Scope of Delegation')
                    ->helperText('Describe which duties/responsibilities are being delegated.')
                    ->columnSpanFull()
                    ->rows(3),

                Textarea::make('notes')
                    ->label('Additional Notes')
                    ->columnSpanFull()
                    ->rows(2),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('delegator_name')
                    ->label('Delegator')
                    ->getStateUsing(fn ($record) => $record->delegator?->full_name ?? '—')
                    ->searchable(query: fn ($query, $search) => $query->whereHas('delegator', fn ($q) => $q->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%")))
                    ->weight('semibold'),

                TextColumn::make('delegate_name')
                    ->label('Acting Employee')
                    ->getStateUsing(fn ($record) => $record->delegate?->full_name ?? '—')
                    ->searchable(query: fn ($query, $search) => $query->whereHas('delegate', fn ($q) => $q->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%"))),

                TextColumn::make('subject')->limit(40),

                TextColumn::make('reason')->limit(25)->placeholder('–'),

                TextColumn::make('start_date')->date()->sortable(),
                TextColumn::make('end_date')->date()->placeholder('Open')->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Active'    => 'success',
                        'Completed' => 'info',
                        'Cancelled' => 'danger',
                        default     => 'gray',
                    }),

                TextColumn::make('approver.name')->label('Approved By')->placeholder('–'),
            ])
            ->defaultSort('start_date', 'desc')
            ->filters([
                SelectFilter::make('status')->options([
                    'Active'    => 'Active',
                    'Completed' => 'Completed',
                    'Cancelled' => 'Cancelled',
                ]),
            ])
            ->recordActions([
                // ── Approve / Activate ───────────────────────────
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (Delegation $record) =>
                        $record->status === 'Active'
                        && is_null($record->approved_by)
                        && (auth()->user()->isHrDirector() || auth()->user()->isHrOfficer() || auth()->user()->isSuperAdmin())
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Approve Delegation')
                    ->modalDescription('Confirm that you are approving this duty delegation.')
                    ->modalSubmitActionLabel('Approve')
                    ->action(function (Delegation $record) {
                        $record->update(['approved_by' => auth()->id()]);
                        Notification::make()->title('Delegation Approved ✓')->success()->send();
                    }),

                // ── Mark Completed ───────────────────────────────
                Action::make('complete')
                    ->label('Complete')
                    ->icon('heroicon-o-check-circle')
                    ->color('info')
                    ->visible(fn (Delegation $record) => $record->status === 'Active')
                    ->requiresConfirmation()
                    ->modalHeading('Mark as Completed')
                    ->modalDescription('Mark this delegation as completed when the period is over.')
                    ->modalSubmitActionLabel('Mark Completed')
                    ->action(function (Delegation $record) {
                        $record->update(['status' => 'Completed']);
                        Notification::make()->title('Delegation marked as Completed')->success()->send();
                    }),

                // ── Cancel ───────────────────────────────────────
                Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Delegation $record) => $record->status === 'Active')
                    ->requiresConfirmation()
                    ->modalHeading('Cancel Delegation')
                    ->modalDescription('Are you sure you want to cancel this delegation?')
                    ->modalSubmitActionLabel('Cancel Delegation')
                    ->action(function (Delegation $record) {
                        $record->update(['status' => 'Cancelled']);
                        Notification::make()->title('Delegation cancelled')->danger()->send();
                    }),

                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListDelegations::route('/'),
            'create' => CreateDelegation::route('/create'),
            'edit'   => EditDelegation::route('/{record}/edit'),
        ];
    }
}

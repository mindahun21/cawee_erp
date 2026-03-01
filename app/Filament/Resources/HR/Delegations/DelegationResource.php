<?php

namespace App\Filament\Resources\HR\Delegations;

use App\Filament\Resources\HR\Delegations\Pages\CreateDelegation;
use App\Filament\Resources\HR\Delegations\Pages\EditDelegation;
use App\Filament\Resources\HR\Delegations\Pages\ListDelegations;
use App\Models\Delegation;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
                    ->relationship('delegator', 'first_name')
                    ->getOptionLabelFromRecordUsing(fn ($r) => $r?->full_name ?? '—')
                    ->searchable()
                    ->preload()
                    ->required(),

                Select::make('delegate_id')
                    ->label('Acting Employee (Covering)')
                    ->relationship('delegate', 'first_name')
                    ->getOptionLabelFromRecordUsing(fn ($r) => $r?->full_name ?? '—')
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

                Select::make('approved_by')
                    ->label('Approved By')
                    ->relationship('approver', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable(),

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
                TextColumn::make('delegator.first_name')
                    ->label('Delegator')
                    ->getStateUsing(fn ($r) => $r->delegator?->full_name)
                    ->searchable()
                    ->weight('semibold'),

                TextColumn::make('delegate.first_name')
                    ->label('Acting Employee')
                    ->getStateUsing(fn ($r) => $r->delegate?->full_name)
                    ->searchable(),

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

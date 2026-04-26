<?php

namespace App\Filament\Resources\HR\Settings;

use App\Models\HrLeaveType;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Filament\Resources\HR\Settings\LeaveTypeResource\Pages;
use UnitEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use App\Traits\BelongsToModule;

class LeaveTypeResource extends Resource
{
    use BelongsToModule;
    protected static ?string $model = HrLeaveType::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static string|UnitEnum|null $navigationGroup = 'Human Resources';

    protected static ?string $navigationLabel = 'Leave Types';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Basic Information')
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(150),

                    TextInput::make('max_days')
                        ->label('Max Days per Request')
                        ->numeric()
                        ->minValue(0)
                        ->helperText('0 = unlimited')
                        ->required(),

                    TextInput::make('default_days')
                        ->label('Default Days (0 = user enters)')
                        ->numeric()
                        ->minValue(0)
                        ->required(),

                    Textarea::make('description')
                        ->rows(2)
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Section::make('Leave Type Flags')
                ->description('These flags control exactly how this leave type behaves.')
                ->schema([
                    Toggle::make('is_annual')
                        ->label('Annual Leave (cumulative, accruing)')
                        ->helperText('Only ONE leave type should be annual. This type uses the balance calculation engine.'),

                    Toggle::make('is_paid')
                        ->label('Paid Leave')
                        ->helperText('Employee receives full salary during this leave.'),

                    Toggle::make('is_working_days')
                        ->label('Working Days Only')
                        ->helperText('If ON: Sundays and public holidays are skipped when computing the end date — so a "5-day" request excludes non-working days.'),

                    Toggle::make('is_hourly')
                        ->label('Hourly Leave')
                        ->helperText('Partial-day leave measured in hours, not days.'),

                    Toggle::make('is_fixed')
                        ->label('Fixed Duration')
                        ->helperText('The number of days is predetermined (e.g. maternity = 90 days always).'),

                    Toggle::make('requires_document')
                        ->label('Requires Supporting Document')
                        ->helperText('Employee must upload a document (e.g. medical certificate).'),

                    Toggle::make('is_active')
                        ->label('Active')
                        ->default(true),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                IconColumn::make('is_annual')
                    ->boolean()
                    ->label('Annual'),

                IconColumn::make('is_paid')
                    ->boolean()
                    ->label('Paid'),

                IconColumn::make('is_working_days')
                    ->boolean()
                    ->label('Working Days'),

                IconColumn::make('is_fixed')
                    ->boolean()
                    ->label('Fixed'),

                IconColumn::make('is_hourly')
                    ->boolean()
                    ->label('Hourly'),

                TextColumn::make('max_days')
                    ->label('Max Days')
                    ->formatStateUsing(fn($state) => $state === 0 ? '—' : $state),

                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
            ])
            ->filters([])
            ->recordActions([
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
            'index' => Pages\ManageLeaveTypes::route('/'),
        ];
    }
}

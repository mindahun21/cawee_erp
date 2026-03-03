<?php

namespace App\Filament\Resources\HR\PerDiemRates;

use App\Filament\Resources\HR\PerDiemRates\Pages\ManagePerDiemRates;
use App\Models\PerDiemRate;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PerDiemRateResource extends Resource
{
    protected static ?string $model = PerDiemRate::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static string|\UnitEnum|null $navigationGroup = 'Human Resources';

    protected static ?string $navigationParentItem = 'HR Settings';

    protected static ?string $navigationLabel = 'Per Diem Rates';

    protected static ?int $navigationSort = 15;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('label')->required()->maxLength(150)->columnSpanFull(),

            TextInput::make('position_pattern')
                ->label('Position (optional filter)')
                ->helperText('Leave blank to apply to all positions')
                ->maxLength(150),

            Select::make('project_id')
                ->label('Project (optional)')
                ->relationship('project', 'project_name')
                ->searchable()->preload()->nullable(),

            Select::make('location_id')
                ->label('Location (optional)')
                ->relationship('location', 'location_name')
                ->searchable()->preload()->nullable(),

            TextInput::make('rate_per_day')
                ->required()
                ->numeric()
                ->prefix('ETB')
                ->minValue(0),

            TextInput::make('currency')->default('ETB')->maxLength(10),

            DatePicker::make('effective_from'),
            DatePicker::make('effective_to'),

            Toggle::make('is_active')->default(true)->inline(false),

            Textarea::make('remarks')->rows(3)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label')->searchable()->sortable()->weight('semibold'),
                TextColumn::make('position_pattern')->label('Position')->placeholder('All'),
                TextColumn::make('project.project_name')->label('Project')->placeholder('All'),
                TextColumn::make('location.location_name')->label('Location')->placeholder('All'),
                TextColumn::make('rate_per_day')
                    ->label('Rate/Day')
                    ->numeric(decimalPlaces: 2)
                    ->prefix('ETB '),
                TextColumn::make('effective_from')->date()->toggleable(),
                TextColumn::make('effective_to')->date()->toggleable(),
                TextColumn::make('is_active')->label('Active')
                    ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'gray'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePerDiemRates::route('/'),
        ];
    }
}

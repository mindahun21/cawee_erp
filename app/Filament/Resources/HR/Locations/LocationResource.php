<?php

namespace App\Filament\Resources\HR\Locations;

use App\Filament\Resources\HR\Locations\Pages\ManageLocations;
use App\Models\Location;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LocationResource extends Resource
{
    protected static ?string $model = Location::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|\UnitEnum|null $navigationGroup = 'Human Resources';

    protected static ?string $navigationParentItem = 'HR Settings';

    protected static ?string $navigationLabel = 'Locations';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'location_name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('location_name')
                ->required()
                ->maxLength(150),

            Select::make('type')
                ->options([
                    'Head Office'  => 'Head Office',
                    'Field Office' => 'Field Office',
                    'Factory'      => 'Factory',
                    'Bakery'       => 'Bakery',
                    'Guesthouse'   => 'Guesthouse',
                ])
                ->required(),

            Textarea::make('address')
                ->rows(3)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('location_name')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Head Office'  => 'primary',
                        'Field Office' => 'info',
                        'Factory'      => 'warning',
                        'Bakery'       => 'success',
                        'Guesthouse'   => 'gray',
                        default        => 'gray',
                    }),

                TextColumn::make('address')
                    ->limit(60)
                    ->toggleable(),

                TextColumn::make('employees_count')
                    ->label('Employees')
                    ->counts('employees')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'Head Office'  => 'Head Office',
                        'Field Office' => 'Field Office',
                        'Factory'      => 'Factory',
                        'Bakery'       => 'Bakery',
                        'Guesthouse'   => 'Guesthouse',
                    ]),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageLocations::route('/'),
        ];
    }
}

<?php

namespace App\Filament\Resources\HR\Settings;

use App\Models\Landlord;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LandlordResource extends Resource
{
    protected static ?string $model = Landlord::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHomeModern;

    protected static string|\UnitEnum|null $navigationGroup = 'Human Resources';

    protected static ?string $navigationParentItem = 'HR Settings';

    protected static ?string $navigationLabel = 'Landlords';

    protected static ?int $navigationSort = 31;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(150),
            TextInput::make('phone')->maxLength(50),
            TextInput::make('email')->email()->maxLength(150),
            Toggle::make('is_active')->default(true),
            Textarea::make('address')->rows(2)->columnSpanFull(),
            Textarea::make('notes')->rows(2)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable()->weight('semibold'),
                TextColumn::make('phone')->searchable(),
                TextColumn::make('email')->searchable(),
                IconColumn::make('is_active')->boolean(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageLandlords::route('/'),
        ];
    }
}


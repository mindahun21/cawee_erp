<?php

namespace App\Filament\Resources\Settings;

use App\Filament\Clusters\Settings;
use App\Models\Depreciation;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use BackedEnum;

class DepreciationResource extends Resource
{
    protected static ?string $model = Depreciation::class;

    protected static string|null $cluster = Settings::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationLabel = 'Depreciation';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('months')
                    ->required()
                    ->numeric()
                    ->label('Number of Months'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('months')
                    ->label('Months')
                    ->sortable(),
                TextColumn::make('assets_count')
                    ->label('Assets Linked')
                    ->counts('assets'),
            ])
            ->filters([
                //
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\Settings\DepreciationResource\Pages\ManageDepreciations::route('/'),
        ];
    }
}

<?php

namespace App\Filament\Resources\Settings;

use App\Filament\Clusters\Settings;
use App\Models\AcquisitionType;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use BackedEnum;

class AcquisitionTypeResource extends Resource
{
    protected static ?string $model = AcquisitionType::class;

    protected static string|null $cluster = Settings::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationLabel = 'Acquisition Types';

    protected static ?int $navigationSort = 13;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Textarea::make('description')
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('description')
                    ->limit(50),
                TextColumn::make('assets_count')
                    ->label('Assets')
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
            'index' => \App\Filament\Resources\Settings\AcquisitionTypeResource\Pages\ManageAcquisitionTypes::route('/'),
        ];
    }
}

<?php

namespace App\Filament\Resources\Settings;

use App\Filament\Clusters\Settings;
use App\Models\AssetStatus;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use BackedEnum;

class AssetStatusResource extends Resource
{
    protected static ?string $model = AssetStatus::class;

    protected static string|null $cluster = Settings::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-check-circle';

    protected static ?string $navigationLabel = 'Asset Statuses';

    protected static ?int $navigationSort = 12;



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
            'index' => \App\Filament\Resources\Settings\AssetStatusResource\Pages\ManageAssetStatuses::route('/'),
        ];
    }
}

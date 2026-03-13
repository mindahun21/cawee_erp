<?php

namespace App\Filament\Resources\Settings;

use App\Filament\Clusters\Settings;
use App\Models\AssetManufacturer;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use BackedEnum;

class AssetManufacturerResource extends Resource
{
    protected static ?string $model = AssetManufacturer::class;

    protected static string|null $cluster = Settings::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationLabel = 'Asset Manufacturers';

    protected static ?int $navigationSort = 15;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('url')
                    ->label('URL')
                    ->url()
                    ->maxLength(255),
                TextInput::make('support_url')
                    ->label('Support URL')
                    ->url()
                    ->maxLength(255),
                TextInput::make('support_phone')
                    ->label('Support Phone')
                    ->tel()
                    ->maxLength(255),
                TextInput::make('support_email')
                    ->label('Support Email')
                    ->email()
                    ->maxLength(255),
                FileUpload::make('image')
                    ->label('Upload Image')
                    ->image()
                    ->directory('asset-manufacturers')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->label('Logo'),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('url')
                    ->label('URL')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('support_email')
                    ->label('Support Email')
                    ->toggleable(),
                TextColumn::make('support_phone')
                    ->label('Support Phone')
                    ->toggleable(),
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
            'index' => \App\Filament\Resources\Settings\AssetManufacturerResource\Pages\ManageAssetManufacturers::route('/'),
        ];
    }
}

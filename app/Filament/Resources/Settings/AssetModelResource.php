<?php

namespace App\Filament\Resources\Settings;

use App\Filament\Clusters\Settings;
use App\Models\AssetModel;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Checkbox;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Schemas\Components\Section;
use BackedEnum;

class AssetModelResource extends Resource
{
    protected static ?string $model = AssetModel::class;

    protected static string|null $cluster = Settings::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'Asset Models';

    protected static ?int $navigationSort = 16;



    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Model Name')
                    ->required()
                    ->maxLength(255),
                 Select::make('asset_type_id')
                    ->label('Asset Type')
                    ->relationship('type', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        TextInput::make('name')->required(),
                    ]),
                 Select::make('asset_manufacturer_id')
                    ->label('Manufacturer')
                    ->relationship('manufacturer', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        TextInput::make('name')->required(),
                    ]),
                 Select::make('asset_category_id')
                    ->label('Category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        TextInput::make('name')->required(),
                        TextInput::make('useful_life')->numeric(),
                        Textarea::make('description'),
                    ]),
                TextInput::make('model_number')
                    ->label('Model NO.')
                    ->maxLength(255),
                 Select::make('depreciation_id')
                    ->label('Depreciation')
                    ->relationship('depreciation', 'name')
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        TextInput::make('name')->required(),
                        TextInput::make('months')->numeric()->required(),
                    ]),
                TextInput::make('eol_months')
                    ->label('EOL (months)')
                    ->numeric()
                    ->minValue(0),
                Checkbox::make('is_requestable')
                    ->label('Users may request this model'),
                Textarea::make('note')
                    ->maxLength(65535)
                    ->columnSpanFull(),
                FileUpload::make('image')
                    ->label('Upload Image')
                    ->image()
                    ->directory('asset-models')
                    ->columnSpanFull(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Model Information')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')
                            ->label('Model Name'),
                        TextEntry::make('type.name')
                            ->label('Asset Type'),
                        TextEntry::make('manufacturer.name')
                            ->label('Manufacturer'),
                        TextEntry::make('category.name')
                            ->label('Category'),
                        TextEntry::make('model_number')
                            ->label('Model NO.'),
                        TextEntry::make('depreciation.name')
                            ->label('Depreciation'),
                        TextEntry::make('eol_months')
                            ->label('EOL (months)'),
                        IconEntry::make('is_requestable')
                            ->label('Requestable')
                            ->boolean(),
                        TextEntry::make('note')
                            ->columnSpanFull(),
                        ImageEntry::make('image')
                            ->label('Model Image')
                            ->columnSpanFull(),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image'),
                TextColumn::make('name')
                    ->label('Model Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('manufacturer.name')
                    ->label('Manufacturer')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type.name')
                    ->label('Asset Type')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label('Category')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('model_number')
                    ->label('Model NO.')
                    ->searchable(),
                IconColumn::make('is_requestable')
                    ->label('Requestable')
                    ->boolean(),
                TextColumn::make('depreciation.name')
                    ->label('Depreciation')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
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
            'index' => \App\Filament\Resources\Settings\AssetModelResource\Pages\ManageAssetModels::route('/'),
        ];
    }
}
